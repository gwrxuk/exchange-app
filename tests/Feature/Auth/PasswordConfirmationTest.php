<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_confirmed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/confirm-password', [
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password confirmed']);

        $response->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/confirm-password', [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $response->assertSessionMissing('auth.password_confirmed_at');
    }
}
