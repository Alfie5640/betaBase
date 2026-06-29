<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClimbingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(): User {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function makeFriends(User $a, User $b): void {
        \App\Models\Friendship::create([
            'user_id'   => $a->id,
            'friend_id' => $b->id,
            'status'    => 'accepted',
        ]);
    }

    public function test_user_can_create_session(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->postJson('/api/sessions', [
            'place'      => 'Boulder World',
            'date'       => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '10:00',
            'time_end'   => '12:00',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('climbing_sessions', [
            'place'   => 'Boulder World',
            'user_id' => $user->id,
        ]);
    }

    public function test_create_session_requires_place_date_and_time(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->postJson('/api/sessions', []);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_create_session(): void {
        $response = $this->postJson('/api/sessions', [
            'place'      => 'Boulder World',
            'date'       => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '10:00',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_see_friends_sessions(): void {
        $user   = $this->verifiedUser();
        $friend = $this->verifiedUser();
        $this->makeFriends($user, $friend);

        ClimbingSession::create([
            'user_id'    => $friend->id,
            'place'      => 'Climbing Gym',
            'date'       => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '09:00',
        ]);

        $response = $this->actingAs($user)->getJson('/api/sessions');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonCount(1, 'sessions');
    }

    public function test_user_cannot_see_strangers_sessions(): void {
        $user    = $this->verifiedUser();
        $stranger = $this->verifiedUser();

        ClimbingSession::create([
            'user_id'    => $stranger->id,
            'place'      => 'Secret Gym',
            'date'       => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '09:00',
        ]);

        $response = $this->actingAs($user)->getJson('/api/sessions');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'sessions');
    }

    public function test_user_can_join_friends_session(): void {
        $user   = $this->verifiedUser();
        $friend = $this->verifiedUser();
        $this->makeFriends($user, $friend);

        $session = ClimbingSession::create([
            'user_id'    => $friend->id,
            'place'      => 'Climbing Gym',
            'date'       => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '09:00',
        ]);

        $response = $this->actingAs($user)->postJson("/api/sessions/{$session->id}/join");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('session_attendees', [
            'session_id' => $session->id,
            'user_id'    => $user->id,
        ]);
    }

    public function test_user_cannot_join_strangers_session(): void {
        $user    = $this->verifiedUser();
        $stranger = $this->verifiedUser();

        $session = ClimbingSession::create([
            'user_id'    => $stranger->id,
            'place'      => 'Secret Gym',
            'date'       => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '09:00',
        ]);

        $response = $this->actingAs($user)->postJson("/api/sessions/{$session->id}/join");

        $response->assertStatus(403);
    }

    public function test_user_cannot_join_own_session(): void {
        $user = $this->verifiedUser();

        $session = ClimbingSession::create([
            'user_id'    => $user->id,
            'place'      => 'My Gym',
            'date'       => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '09:00',
        ]);

        $response = $this->actingAs($user)->postJson("/api/sessions/{$session->id}/join");

        $response->assertStatus(403);
    }

    public function test_user_can_leave_session(): void {
        $user   = $this->verifiedUser();
        $friend = $this->verifiedUser();
        $this->makeFriends($user, $friend);

        $session = ClimbingSession::create([
            'user_id'    => $friend->id,
            'place'      => 'Climbing Gym',
            'date'       => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '09:00',
        ]);

        $session->attendees()->attach($user->id);

        $response = $this->actingAs($user)->deleteJson("/api/sessions/{$session->id}/leave");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('session_attendees', [
            'session_id' => $session->id,
            'user_id'    => $user->id,
        ]);
    }

    public function test_user_cannot_leave_session_they_are_not_attending(): void {
        $user   = $this->verifiedUser();
        $friend = $this->verifiedUser();
        $this->makeFriends($user, $friend);

        $session = ClimbingSession::create([
            'user_id'    => $friend->id,
            'place'      => 'Climbing Gym',
            'date'       => now()->addDays(1)->format('Y-m-d'),
            'time_start' => '09:00',
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/sessions/{$session->id}/leave");

        $response->assertStatus(403);
    }
}