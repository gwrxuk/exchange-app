<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Disable the "data" wrapper.
     */
    public static $wrap = null;

    /**
     * The HTTP status code for the response.
     */
    protected int $statusCode = 200;

    /**
     * Set the HTTP status code.
     */
    public function withStatusCode(int $code): self
    {
        $this->statusCode = $code;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'side' => $this->side,
            'price' => (float) $this->price,
            'amount' => (float) $this->amount,
            'remaining_amount' => (float) $this->remaining_amount,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Customize the response for the resource.
     */
    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        $response->setStatusCode($this->statusCode);
    }
}
