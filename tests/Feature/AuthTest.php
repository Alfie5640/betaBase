<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void {
        $response = $this->postJson('/api/register', [
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['username' => 'testuser']);
    }

    public function test_register_requires_all_fields(): void {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422);
    }

    public function test_register_requires_unique_username(): void {
        User::factory()->create(['username' => 'testuser']);

        $response = $this->postJson('/api/register', [
            'username'              => 'testuser',
            'email'                 => 'other@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_requires_unique_email(): void {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/register', [
            'username'              => 'newuser',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_requires_password_confirmation(): void {
        $response = $this->postJson('/api/register', [
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_requires_minimum_password_length(): void {
        $response = $this->postJson('/api/register', [
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_sends_verification_email(): void {
        \Illuminate\Support\Facades\Notification::fake();

        $response = $this->postJson('/api/register', [
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            User::where('email', 'test@example.com')->first(),
            \Illuminate\Auth\Notifications\VerifyEmail::class
        );
    }

    public function test_verified_user_can_login(): void {
        $user = User::factory()->create([
            'password'          => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    public function test_unverified_user_cannot_login(): void {
        $user = User::factory()->create([
            'password'          => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
                 ->assertJsonPath('unverified', true);
    }

    public function test_login_fails_with_wrong_password(): void {
        $user = User::factory()->create([
            'password'          => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_nonexistent_user(): void {
        $response = $this->postJson('/api/login', [
            'username' => 'nobody',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }


    public function test_authenticated_user_can_logout(): void {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user, 'web')
                        ->withSession(['_token' => 'test'])
                        ->postJson('/api/logout');

        $response->assertStatus(200)
                ->assertJsonPath('success', true);
    }

    public function test_unauthenticated_user_cannot_logout(): void {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }


    public function test_login_is_rate_limited(): void {
        $user = User::factory()->create([
            'password'          => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'username' => $user->username,
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }
}