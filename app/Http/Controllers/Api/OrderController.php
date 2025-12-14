<?php

namespace App\Http\Controllers\Api;

use App\Data\Order\StoreOrderData;
use App\Data\Order\IndexOrderData;
use App\Data\Order\OrderData;
use App\Data\Order\CancelOrderData;
use App\Http\Controllers\Controller;
use App\Services\Contracts\OrderServiceInterface;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderServiceInterface $orderService) {
        $this->orderService = $orderService;
    }

    public function index(IndexOrderData $data)
    {
        $orders = $this->orderService->findOpenBySymbol($data->symbol);
        return OrderData::collect($orders);
    }

    public function store(StoreOrderData $data)
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
            
            return OrderData::from($order);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function cancel(CancelOrderData $data)
    {
        $user = request()->user();
        
        try {
            $this->orderService->cancelOrder($data->id, $user);
            return response()->json(['message' => 'Order cancelled']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
