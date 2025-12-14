<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderBookResource extends JsonResource
{
    /**
     * Disable the "data" wrapper.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $orders = $this->resource;

        $buys = $orders->where('side', 'buy')
            ->sortByDesc('price')
            ->values();

        $sells = $orders->where('side', 'sell')
            ->sortBy('price')
            ->values();

        return [
            'buys' => OrderResource::collection($buys),
            'sells' => OrderResource::collection($sells),
        ];
    }
}
