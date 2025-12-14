<?php

namespace App\Services\Eloquent;

use App\Models\Order;
use App\Models\User;
use App\Services\Contracts\OrderServiceInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\AssetRepositoryInterface;
use App\Services\MatchingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    protected $orders;
    protected $users;
    protected $assets;
    protected $matcher;

    public function __construct(
        OrderRepositoryInterface $orders,
        UserRepositoryInterface $users,
        AssetRepositoryInterface $assets,
        MatchingService $matcher
    ) {
        $this->orders = $orders;
        $this->users = $users;
        $this->assets = $assets;
        $this->matcher = $matcher;
    }

    public function findOpenBySymbol(string $symbol): Collection
    {
        return $this->orders->findOpenBySymbol($symbol);
    }

    public function createOrder(User $user, string $symbol, string $side, float $price, float $amount): Order
    {
        $totalCost = $price * $amount;

        $order = DB::transaction(function () use ($user, $symbol, $side, $price, $amount, $totalCost) {
            // Lock user row for balance update
            $user = $this->users->lockForUpdate($user->id);

            if ($side === 'buy') {
                if ($user->balance < $totalCost) {
                    throw new \Exception('Insufficient funds');
                }
                $this->users->update($user, ['balance' => $user->balance - $totalCost]);
            } else {
                // Sell side: Lock asset
                $asset = $this->assets->lockForUpdate($user->id, $symbol);

                if (!$asset || $asset->amount < $amount) {
                    throw new \Exception('Insufficient assets');
                }

                $this->assets->update($asset, [
                    'amount' => $asset->amount - $amount,
                    'locked_amount' => $asset->locked_amount + $amount
                ]);
            }

            return $this->orders->create([
                'user_id' => $user->id,
                'symbol' => $symbol,
                'side' => $side,
                'price' => $price,
                'amount' => $amount,
                'remaining_amount' => $amount,
                'status' => Order::STATUS_OPEN,
            ]);
        });

        // Dispatch matching
        $this->matcher->match($order);

        return $order;
    }

    public function cancelOrder(int $orderId, User $user): void
    {
        DB::transaction(function () use ($user, $orderId) {
            $order = $this->orders->lockForUpdate($orderId);

            if ($order->user_id !== $user->id) {
                throw new \Exception('Unauthorized');
            }

            if ($order->status !== Order::STATUS_OPEN) {
                throw new \Exception('Order cannot be cancelled');
            }

            $this->orders->update($order, ['status' => Order::STATUS_CANCELLED]);

            // Refund
            if ($order->side === 'buy') {
                $refund = $order->remaining_amount * $order->price; // Refund remaining value
                $user = $this->users->lockForUpdate($user->id);
                $this->users->update($user, ['balance' => $user->balance + $refund]);
            } else {
                $asset = $this->assets->lockForUpdate($user->id, $order->symbol);
                
                $this->assets->update($asset, [
                    'locked_amount' => $asset->locked_amount - $order->remaining_amount,
                    'amount' => $asset->amount + $order->remaining_amount
                ]);
            }
        });
    }
}

