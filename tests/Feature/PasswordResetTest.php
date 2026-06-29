<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_link(): void {
        $user = User::factory()->create();

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    public function test_password_reset_returns_success_for_unknown_email(): void {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        // Should not reveal whether email exists
        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    public function test_forgot_password_requires_email(): void {
        $response = $this->postJson('/api/forgot-password', []);

        $response->assertStatus(422);
    }

    public function test_user_can_reset_password_with_valid_token(): void {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_user_cannot_reset_password_with_invalid_token(): void {
        $user = User::factory()->create();

        $response = $this->postJson('/api/reset-password', [
            'token'                 => 'invalidtoken',
            'email'                 => $user->email,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
                 ->assertJsonPath('success', false);
    }

    public function test_reset_password_requires_minimum_length(): void {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_requires_confirmation(): void {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'newpassword123',
            'password_confirmation' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_forgot_password_is_rate_limited(): void {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/forgot-password', ['email' => 'test@example.com']);
        }

        $response = $this->postJson('/api/forgot-password', ['email' => 'test@example.com']);

        $response->assertStatus(429);
    }
}