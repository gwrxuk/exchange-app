<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_profile(): void
    {
        $user = User::factory()->create();
        Asset::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => 1.5,
            'locked_amount' => 0.5
        ]);

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'balance'],
                'assets' => [['symbol', 'amount', 'locked_amount']]
            ]);
    }

    public function test_user_can_view_my_orders(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/my-orders');

        $response->assertStatus(200);
    }
}

