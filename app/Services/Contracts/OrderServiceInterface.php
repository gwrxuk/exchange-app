<?php

namespace App\Services\Contracts;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface OrderServiceInterface
{
    public function findOpenBySymbol(string $symbol): Collection;
    public function createOrder(User $user, string $symbol, string $side, float $price, float $amount): Order;
    public function cancelOrder(int $orderId, User $user): void;
}

