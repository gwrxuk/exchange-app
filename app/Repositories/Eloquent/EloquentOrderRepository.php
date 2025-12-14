<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function find(int $id): ?Order
    {
        return Order::find($id);
    }

    public function findOpenBySymbol(string $symbol): Collection
    {
        return Order::where('symbol', $symbol)
            ->where('status', Order::STATUS_OPEN)
            ->orderBy('price', 'desc')
            ->get();
    }

    public function findOpenBuyOrders(string $symbol): ?Order
    {
         // Find highest buy price (Logic from MatchingService)
         // Not strictly needed here if MatchingService builds query, but good to have
         return null; 
    }

    public function findOpenSellOrders(string $symbol): ?Order
    {
         return null;
    }

    public function update(Order $order, array $data): bool
    {
        return $order->update($data);
    }

    public function lockForUpdate(int $id): Order
    {
        return Order::lockForUpdate()->find($id);
    }
}

