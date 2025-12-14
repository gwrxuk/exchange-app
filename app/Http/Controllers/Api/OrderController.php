<?php

namespace App\Http\Controllers\Api;

use App\Data\Order\StoreOrderData;
use App\Data\Order\IndexOrderData;
use App\Data\Order\OrderData;
use App\Data\Order\CancelOrderData;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\AssetRepositoryInterface;
use App\Services\MatchingService;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class OrderController extends Controller
{
    protected $orders;
    protected $users;
    protected $assets;

    public function __construct(
        OrderRepositoryInterface $orders,
        UserRepositoryInterface $users,
        AssetRepositoryInterface $assets
    ) {
        $this->orders = $orders;
        $this->users = $users;
        $this->assets = $assets;
    }

    public function index(IndexOrderData $data)
    {
        $orders = $this->orders->findOpenBySymbol($data->symbol);
        return OrderData::collect($orders);
    }

    public function store(StoreOrderData $data, MatchingService $matcher)
    {
        $user = request()->user();
        $totalCost = $data->price * $data->amount;

        try {
            $order = DB::transaction(function () use ($user, $data, $totalCost) {
                // Lock user row for balance update
                $user = $this->users->lockForUpdate($user->id);

                if ($data->side === 'buy') {
                    if ($user->balance < $totalCost) {
                        throw new \Exception('Insufficient funds');
                    }
                    $this->users->update($user, ['balance' => $user->balance - $totalCost]);
                } else {
                    // Sell side: Lock asset
                    $asset = $this->assets->lockForUpdate($user->id, $data->symbol);

                    if (!$asset || $asset->amount < $data->amount) {
                        throw new \Exception('Insufficient assets');
                    }

                    $this->assets->update($asset, [
                        'amount' => $asset->amount - $data->amount,
                        'locked_amount' => $asset->locked_amount + $data->amount
                    ]);
                }

                return $this->orders->create([
                    'user_id' => $user->id,
                    'symbol' => $data->symbol,
                    'side' => $data->side,
                    'price' => $data->price,
                    'amount' => $data->amount,
                    'remaining_amount' => $data->amount,
                    'status' => Order::STATUS_OPEN,
                ]);
            });

            // Dispatch matching
            $matcher->match($order);
            
            return OrderData::from($order);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function cancel(CancelOrderData $data)
    {
        $id = $data->id;
        $user = request()->user();
        
        try {
            DB::transaction(function () use ($user, $id) {
                // Order existence and ownership is already checked in FormRequest authorization
                // But we still need to lock it for update and ensure it's open
                // With DTO, simple ID existence is checked. Ownership we check here or custom validation.
                $order = $this->orders->lockForUpdate($id);

                // DTO only validated ID exists. Need to check ownership.
                if ($order->user_id !== $user->id) {
                    throw new \Exception('Unauthorized');
                }

                if ($order->status !== Order::STATUS_OPEN) {
                    throw new \Exception('Order cannot be cancelled');
                }

                $this->orders->update($order, ['status' => Order::STATUS_CANCELLED]);

                // Refund
                if ($order->side === 'buy') {
                    $refund = $order->remaining_amount * $order->price; // Refund remaining value
                    $user = $this->users->lockForUpdate($user->id);
                    $this->users->update($user, ['balance' => $user->balance + $refund]);
                } else {
                    $asset = $this->assets->lockForUpdate($user->id, $order->symbol);
                    
                    $this->assets->update($asset, [
                        'locked_amount' => $asset->locked_amount - $order->remaining_amount,
                        'amount' => $asset->amount + $order->remaining_amount
                    ]);
                }
            });

            return response()->json(['message' => 'Order cancelled']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
