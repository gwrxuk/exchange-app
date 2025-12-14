<?php

namespace App\Data\Order;

use Spatie\LaravelData\Data;

class OrderData extends Data
{
    public function __construct(
        public int $id,
        public string $symbol,
        public string $side,
        public float $price,
        public float $amount,
        public float $remaining_amount,
        public int $status,
        public string $created_at,
    ) {}
}

