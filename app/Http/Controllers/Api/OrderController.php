<?php

namespace App\Http\Controllers\Api;

use App\Data\Order\CancelOrderData;
use App\Data\Order\IndexOrderData;
use App\Data\Order\StoreOrderData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\OrderBookResource;
use App\Http\Resources\OrderResource;
use App\Services\Contracts\OrderServiceInterface;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderController extends Controller
{
    public function __construct(
        protected OrderServiceInterface $orderService
    ) {}

    public function index(IndexOrderData $data): JsonResource
    {
        $orders = $this->orderService->findOpenBySymbol($data->symbol);

        return new OrderBookResource($orders);
    }

    public function store(StoreOrderData $data): JsonResource
    {
        $user = request()->user();

        try {
            $order = $this->orderService->createOrder(
                $user,
                $data->symbol,
                $data->side,
                $data->price,
                $data->amount
            );

            return (new OrderResource($order))->withStatusCode(201);
        } catch (\Exception $e) {
            return new ErrorResource($e->getMessage(), 400);
        }
    }

    public function cancel(CancelOrderData $data): JsonResource
    {
        $user = request()->user();

        try {
            $this->orderService->cancelOrder($data->id, $user);

            return new MessageResource('Order cancelled');
        } catch (\Exception $e) {
            return new ErrorResource($e->getMessage(), 400);
        }
    }
}
