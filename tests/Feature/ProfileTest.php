<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(): User {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_user_can_update_password(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->patchJson('/api/profile/password', [
            'current_password'      => 'password',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    public function test_user_cannot_update_password_with_wrong_current(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->patchJson('/api/profile/password', [
            'current_password'      => 'wrongpassword',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(403);
    }

    public function test_password_update_requires_confirmation(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->patchJson('/api/profile/password', [
            'current_password'      => 'password',
            'password'              => 'newpassword123',
            'password_confirmation' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_update_username(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->patchJson('/api/profile/username', [
            'username' => 'newusername',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('username', 'newusername');

        $this->assertDatabaseHas('users', ['username' => 'newusername']);
    }

    public function test_user_cannot_update_to_taken_username(): void {
        $user  = $this->verifiedUser();
        $other = $this->verifiedUser();

        $response = $this->actingAs($user)->patchJson('/api/profile/username', [
            'username' => $other->username,
        ]);

        $response->assertStatus(422);
    }

    public function test_username_update_is_rate_limited(): void {
        $user = $this->verifiedUser();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->patchJson('/api/profile/username', [
                'username' => 'username' . $i,
            ]);
        }

        $response = $this->actingAs($user)->patchJson('/api/profile/username', [
            'username' => 'finalusername',
        ]);

        $response->assertStatus(429);
    }

    public function test_user_can_update_bio(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->postJson('/api/profile/bio', [
            'bio' => 'I love climbing!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('bio', 'I love climbing!');
    }

    public function test_user_can_clear_bio(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->postJson('/api/profile/bio', [
            'bio' => null,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    public function test_bio_cannot_exceed_500_characters(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->postJson('/api/profile/bio', [
            'bio' => str_repeat('a', 501),
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_upload_profile_picture(): void {
        Storage::fake('public');

        $user = $this->verifiedUser();
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $response = $this->actingAs($user)->postJson('/api/profile/picture', [
            'profile_picture' => $file,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    public function test_profile_picture_must_be_an_image(): void {
        Storage::fake('public');

        $user = $this->verifiedUser();
        $file = UploadedFile::fake()->create('malicious.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->postJson('/api/profile/picture', [
            'profile_picture' => $file,
        ]);

        $response->assertStatus(422);
    }

    public function test_profile_picture_cannot_exceed_2mb(): void {
        Storage::fake('public');

        $user = $this->verifiedUser();
        $file = UploadedFile::fake()->image('large.jpg')->size(3000);

        $response = $this->actingAs($user)->postJson('/api/profile/picture', [
            'profile_picture' => $file,
        ]);

        $response->assertStatus(422);
    }
}