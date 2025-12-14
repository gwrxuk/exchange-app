<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    /**
     * Disable the "data" wrapper.
     */
    public static $wrap = null;

    /**
     * The error message to return.
     */
    protected string $error;

    /**
     * The HTTP status code.
     */
    protected int $statusCode;

    /**
     * Create a new resource instance.
     */
    public function __construct(string $error, int $statusCode = 400)
    {
        $this->error = $error;
        $this->statusCode = $statusCode;
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
            'error' => $this->error,
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
