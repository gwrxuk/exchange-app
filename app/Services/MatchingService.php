<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchingService
{
    public function match(Order $order)
    {
        // Loop until order is filled or no more matches
        while ($order->status === Order::STATUS_OPEN && $order->remaining_amount > 0) {

            $match = null;

            if ($order->side === 'buy') {
                // Find lowest sell price <= buy price
                $match = Order::where('symbol', $order->symbol)
                    ->where('side', 'sell')
                    ->where('status', Order::STATUS_OPEN)
                    ->where('price', '<=', $order->price)
                    ->orderBy('price', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();
            } else {
                // Find highest buy price >= sell price
                $match = Order::where('symbol', $order->symbol)
                    ->where('side', 'buy')
                    ->where('status', Order::STATUS_OPEN)
                    ->where('price', '>=', $order->price)
                    ->orderBy('price', 'desc')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $match) {
                break;
            }

            $this->executeTrade($order, $match);

            // Refresh order to get updated remaining_amount
            $order->refresh();
        }
    }

    protected function executeTrade(Order $taker, Order $maker)
    {
        DB::transaction(function () use ($taker, $maker) {
            // Price is always Maker's price (the one sitting in the book)
            $tradePrice = $maker->price;

            // Amount is min of both
            $tradeAmount = min($taker->remaining_amount, $maker->remaining_amount);

            // Commission Rate 1.5%
            $feeRate = 0.015;

            // Update Maker
            $maker->remaining_amount -= $tradeAmount;
            if ($maker->remaining_amount <= 0) {
                $maker->status = Order::STATUS_FILLED;
            }
            $maker->save();

            // Update Taker
            $taker->remaining_amount -= $tradeAmount;
            if ($taker->remaining_amount <= 0) {
                $taker->status = Order::STATUS_FILLED;
            }
            $taker->save();

            // Create Trade Record
            $trade = Trade::create([
                'buyer_id' => $taker->side === 'buy' ? $taker->user_id : $maker->user_id,
                'seller_id' => $taker->side === 'sell' ? $taker->user_id : $maker->user_id,
                'symbol' => $taker->symbol,
                'price' => $tradePrice,
                'amount' => $tradeAmount,
            ]);

            // Settle Balances
            $this->settleBalances($taker, $maker, $tradePrice, $tradeAmount, $feeRate);

            // Broadcast Event
            OrderMatched::dispatch($trade);
        });
    }

    protected function settleBalances($taker, $maker, $price, $amount, $feeRate)
    {
        // Identify Buyer and Seller
        $buyer = $taker->side === 'buy' ? $taker : $maker;
        $seller = $taker->side === 'sell' ? $taker : $maker;

        $totalValue = $price * $amount; // USD value

        // --- Buyer Settlement ---
        // Buyer locked USD ($price * $amount) if they were maker?
        // If Taker was Buyer: Locked `taker->price * amount`. But pays `maker->price * amount`.
        // Difference should be refunded.

        $buyerUser = User::lockForUpdate()->find($buyer->user_id);

        // Calculate Cost
        $buyerCost = $totalValue; // Actual cost

        // Release Locked Funds for Buyer
        if ($buyer->id === $taker->id) {
            // Taker (Buyer) locked `taker->price * amount`
            // Refund difference
            $locked = $taker->price * $amount;
            $diff = $locked - $buyerCost;
            if ($diff > 0) {
                $buyerUser->balance += $diff;
            }
        } else {
            // Maker (Buyer) locked `maker->price * amount` which is exactly `buyerCost`.
            // No refund needed on cost basis.
        }

        // Buyer Receives Asset (Minus Asset Fee?) OR Pays USD Fee?
        // Let's deduct Fee from Asset received.
        $assetReceived = $amount * (1 - $feeRate);

        // Add Asset to Buyer
        $buyerAsset = Asset::firstOrCreate(
            ['user_id' => $buyer->user_id, 'symbol' => $buyer->symbol],
            ['amount' => 0, 'locked_amount' => 0]
        );
        $buyerAsset->amount += $assetReceived;
        $buyerAsset->save();

        // Save Buyer Balance (already deducted when ordering, possibly refunded diff)
        $buyerUser->save();

        // --- Seller Settlement ---
        $sellerUser = User::lockForUpdate()->find($seller->user_id);

        // Seller locked Asset ($amount).
        // Release Locked Asset (it's gone now).
        $sellerAsset = Asset::where('user_id', $seller->user_id)->where('symbol', $seller->symbol)->lockForUpdate()->first();
        $sellerAsset->locked_amount -= $amount;
        $sellerAsset->save();

        // Seller Receives USD (Minus USD Fee?)
        // Let's deduct Fee from USD received.
        $usdReceived = $totalValue * (1 - $feeRate);

        $sellerUser->balance += $usdReceived;
        $sellerUser->save();
    }
}
