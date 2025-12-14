<?php

namespace App\Data\Order;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Exists;

class CancelOrderData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('orders', 'id')]
        public int $id,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            id: (int) $request->route('id'),
        );
    }
}

