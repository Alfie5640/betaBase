<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_access_protected_routes(): void {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)->getJson('/api/sessions');

        $response->assertStatus(403)
                 ->assertJsonPath('unverified', true);
    }

    public function test_verified_user_can_access_protected_routes(): void {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->getJson('/api/sessions');

        $response->assertStatus(200);
    }

    public function test_user_can_verify_email_with_valid_link(): void {
        $user = User::factory()->create(['email_verified_at' => null]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_user_cannot_verify_with_invalid_hash(): void {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->get("/api/email/verify/{$user->id}/invalidhash");

        $response->assertStatus(403);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_already_verified_user_is_redirected(): void {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect();
    }

    public function test_resend_verification_email_by_email(): void {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->postJson('/api/email/resend', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_verification_email_by_username(): void {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->postJson('/api/email/resend-by-username', [
            'username' => $user->username,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_returns_success_for_already_verified_user(): void {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->postJson('/api/email/resend', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', false);
    }

    public function test_resend_is_rate_limited(): void {
        $user = User::factory()->create(['email_verified_at' => null]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/email/resend', ['email' => $user->email]);
        }

        $response = $this->postJson('/api/email/resend', ['email' => $user->email]);

        $response->assertStatus(429);
    }
}