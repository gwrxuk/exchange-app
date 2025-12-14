<?php

namespace App\Data\Order;

use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class CancelOrderData extends Data
{
    public function __construct(
        #[FromRouteParameter('id'), Required, IntegerType, Exists('orders', 'id')]
        public int $id,
    ) {}
}
