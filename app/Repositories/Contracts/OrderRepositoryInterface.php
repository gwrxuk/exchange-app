<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function create(array $data): Order;

    public function find(int $id): ?Order;

    public function findOpenBySymbol(string $symbol): Collection;

    public function findOpenBuyOrders(string $symbol): ?Order;

    public function findOpenSellOrders(string $symbol): ?Order;

    public function update(Order $order, array $data): bool;

    public function lockForUpdate(int $id): Order;
}
