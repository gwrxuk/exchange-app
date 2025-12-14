<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_can_be_requested(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'We have emailed your password reset link.']);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->postJson('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Your password has been reset.']);
            
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        Event::assertDispatched(PasswordReset::class);
    }
}

