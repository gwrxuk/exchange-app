<?php

namespace App\Data\Order;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class IndexOrderData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $symbol,
    ) {}
}

