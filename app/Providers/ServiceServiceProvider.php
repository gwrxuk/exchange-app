<?php

namespace App\Providers;

use App\Services\Contracts\OrderServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use App\Services\Eloquent\OrderService;
use App\Services\Eloquent\UserService;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
