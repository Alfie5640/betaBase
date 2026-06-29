<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendshipTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(): User {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function makeFriends(User $a, User $b): Friendship {
        return Friendship::create([
            'user_id'   => $a->id,
            'friend_id' => $b->id,
            'status'    => 'accepted',
        ]);
    }

    public function test_user_can_send_friend_request(): void {
        $user   = $this->verifiedUser();
        $target = $this->verifiedUser();

        $response = $this->actingAs($user)->postJson('/api/friends/request', [
            'friend_id' => $target->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('friendships', [
            'user_id'   => $user->id,
            'friend_id' => $target->id,
            'status'    => 'pending',
        ]);
    }

    public function test_user_cannot_send_request_to_themselves(): void {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->postJson('/api/friends/request', [
            'friend_id' => $user->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_send_duplicate_friend_request(): void
    {
        $user   = $this->verifiedUser();
        $target = $this->verifiedUser();

        Friendship::create([
            'user_id'   => $user->id,
            'friend_id' => $target->id,
            'status'    => 'pending',
        ]);

        $response = $this->actingAs($user)->postJson('/api/friends/request', [
            'friend_id' => $target->id,
        ]);

        $response->assertStatus(409);
    }

    public function test_user_can_accept_friend_request(): void {
        $sender   = $this->verifiedUser();
        $receiver = $this->verifiedUser();

        $friendship = Friendship::create([
            'user_id'   => $sender->id,
            'friend_id' => $receiver->id,
            'status'    => 'pending',
        ]);

        $response = $this->actingAs($receiver)->postJson('/api/friends/respond', [
            'friendship_id' => $friendship->id,
            'status'        => 'accepted',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('friendships', [
            'id'     => $friendship->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_cannot_respond_to_others_friend_request(): void {
        $sender    = $this->verifiedUser();
        $receiver  = $this->verifiedUser();
        $intruder  = $this->verifiedUser();

        $friendship = Friendship::create([
            'user_id'   => $sender->id,
            'friend_id' => $receiver->id,
            'status'    => 'pending',
        ]);

        $response = $this->actingAs($intruder)->postJson('/api/friends/respond', [
            'friendship_id' => $friendship->id,
            'status'        => 'accepted',
        ]);

        $response->assertStatus(403);
    }


    public function test_user_can_retrieve_friends_list(): void {
        $user   = $this->verifiedUser();
        $friend = $this->verifiedUser();
        $this->makeFriends($user, $friend);

        $response = $this->actingAs($user)->getJson('/api/friends');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonCount(1, 'friends');
    }

    public function test_user_can_search_for_users(): void {
        $user   = $this->verifiedUser();
        $target = User::factory()->create([
            'username'          => 'searchablename',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/friends/search?username=searchable');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonCount(1, 'users');
    }

    public function test_search_does_not_return_self(): void {
        $user = User::factory()->create([
            'username'          => 'mysearchname',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/friends/search?username=mysearchname');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'users');
    }

    public function test_user_can_retrieve_pending_requests(): void {
        $user   = $this->verifiedUser();
        $sender = $this->verifiedUser();

        Friendship::create([
            'user_id'   => $sender->id,
            'friend_id' => $user->id,
            'status'    => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson('/api/friends/pending');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'requests');
    }

    public function test_user_can_remove_friend(): void {
        $user   = $this->verifiedUser();
        $friend = $this->verifiedUser();
        $friendship = $this->makeFriends($user, $friend);

        $response = $this->actingAs($user)->deleteJson("/api/friends/{$friendship->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function test_user_cannot_remove_others_friendship(): void {
        $userA    = $this->verifiedUser();
        $userB    = $this->verifiedUser();
        $intruder = $this->verifiedUser();

        $friendship = $this->makeFriends($userA, $userB);

        $response = $this->actingAs($intruder)->deleteJson("/api/friends/{$friendship->id}");

        $response->assertStatus(404);
    }
}