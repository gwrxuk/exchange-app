<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\OrderController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/my-orders', [ProfileController::class, 'orders']);

    Route::get('/orders', [OrderController::class, 'index']); // Orderbook (maybe public?)
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});

// Public orderbook? The requirement says "Returns all open orders", but dashboard implies authenticated user context.
// But orderbook is usually public.
Route::get('/public/orders', [OrderController::class, 'index']);
