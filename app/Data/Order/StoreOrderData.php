<?php

namespace App\Data\Order;

use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreOrderData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $symbol,

        #[Required, In(['buy', 'sell'])]
        public string $side,

        #[Required, Numeric, GreaterThan(0)]
        public float $price,

        #[Required, Numeric, GreaterThan(0)]
        public float $amount,
    ) {}
}
