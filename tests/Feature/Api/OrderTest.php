<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Asset;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_order_book(): void
    {
        $user = User::factory()->create();
        Order::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000,
            'amount' => 1,
            'remaining_amount' => 1,
            'status' => Order::STATUS_OPEN
        ]);

        $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'symbol', 'side', 'price', 'amount', 'remaining_amount', 'status']
            ]);
    }

    public function test_user_can_place_buy_order(): void
    {
        $user = User::factory()->create(['balance' => 60000]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000,
            'amount' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'status']);
            
        $this->assertEquals(10000, $user->fresh()->balance); // 60000 - 50000
    }

    public function test_user_can_cancel_order(): void
    {
        $user = User::factory()->create(['balance' => 10000]);
        $order = Order::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 50000,
            'amount' => 1,
            'remaining_amount' => 1,
            'status' => Order::STATUS_OPEN
        ]);

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Order cancelled']);
            
        $this->assertEquals(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertEquals(60000, $user->fresh()->balance); // 10000 + 50000
    }
}

