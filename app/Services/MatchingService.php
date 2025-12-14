<?php

namespace App\Services;

use App\Events\OrderBookUpdated;
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
        $symbol = $taker->symbol;

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

            // Broadcast OrderMatched to both parties (private channels)
            OrderMatched::dispatch($trade);
        });

        // Broadcast OrderBookUpdated to all users (public channel)
        OrderBookUpdated::dispatch($symbol, 'order_matched');
    }

    protected function settleBalances($taker, $maker, $price, $amount, $feeRate)
    {
        // Identify Buyer and Seller
        $buyer = $taker->side === 'buy' ? $taker : $maker;
        $seller = $taker->side === 'sell' ? $taker : $maker;

        $totalValue = $price * $amount; // USD value (e.g., 0.01 BTC @ $95,000 = $950)

        // Commission: 1.5% of trade volume
        // Example: $950 * 0.015 = $14.25 USD fee
        // Fee is deducted from SELLER's USD received (consistent approach)
        $fee = $totalValue * $feeRate;

        // --- Buyer Settlement ---
        $buyerUser = User::lockForUpdate()->find($buyer->user_id);

        // Buyer pays the trade value (already locked when order placed)
        $buyerCost = $totalValue;

        // If Buyer was Taker: may have locked more than needed (their limit price vs maker's price)
        // Refund the difference
        if ($buyer->id === $taker->id) {
            $locked = $taker->price * $amount;
            $diff = $locked - $buyerCost;
            if ($diff > 0) {
                $buyerUser->balance += $diff;
            }
        }

        // Buyer receives FULL asset amount (no fee deduction)
        $buyerAsset = Asset::firstOrCreate(
            ['user_id' => $buyer->user_id, 'symbol' => $buyer->symbol],
            ['amount' => 0, 'locked_amount' => 0]
        );
        $buyerAsset->amount += $amount;
        $buyerAsset->save();

        $buyerUser->save();

        // --- Seller Settlement ---
        $sellerUser = User::lockForUpdate()->find($seller->user_id);

        // Release seller's locked asset (it's been transferred to buyer)
        $sellerAsset = Asset::where('user_id', $seller->user_id)
            ->where('symbol', $seller->symbol)
            ->lockForUpdate()
            ->first();
        $sellerAsset->locked_amount -= $amount;
        $sellerAsset->save();

        // Seller receives USD MINUS the 1.5% commission fee
        // Example: $950 - $14.25 = $935.75 USD
        $usdReceived = $totalValue - $fee;

        $sellerUser->balance += $usdReceived;
        $sellerUser->save();
    }
}
