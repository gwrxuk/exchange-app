<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Disable the "data" wrapper.
     */
    public static $wrap = null;

    /**
     * The message to return.
     */
    protected string $message;

    /**
     * Create a new resource instance.
     */
    public function __construct(string $message)
    {
        $this->message = $message;
        parent::__construct(null);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
