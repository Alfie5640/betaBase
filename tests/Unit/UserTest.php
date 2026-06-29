<?php

namespace Tests\Unit;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private function makeFriends(User $a, User $b): void {
        Friendship::create([
            'user_id'   => $a->id,
            'friend_id' => $b->id,
            'status'    => 'accepted',
        ]);
    }

    public function test_user_has_climbing_sessions_relationship(): void {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->climbingSessions()
        );
    }

    public function test_user_has_sent_friend_requests_relationship(): void {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->sentFriendRequests()
        );
    }

    public function test_user_has_received_friend_requests_relationship(): void {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $user->receivedFriendRequests()
        );
    }

    public function test_friends_method_returns_accepted_friends(): void {
        $user    = User::factory()->create();
        $friend  = User::factory()->create();
        $this->makeFriends($user, $friend);

        $friends = $user->friends();

        $this->assertCount(1, $friends);
        $this->assertTrue($friends->contains('id', $friend->id));
    }

    public function test_friends_method_works_both_directions(): void {
        $user   = User::factory()->create();
        $friend = User::factory()->create();
        $this->makeFriends($friend, $user); // friend sent the request

        $friends = $user->friends();

        $this->assertCount(1, $friends);
        $this->assertTrue($friends->contains('id', $friend->id));
    }

    public function test_friends_method_excludes_pending_requests(): void {
        $user   = User::factory()->create();
        $sender = User::factory()->create();

        Friendship::create([
            'user_id'   => $sender->id,
            'friend_id' => $user->id,
            'status'    => 'pending',
        ]);

        $friends = $user->friends();

        $this->assertCount(0, $friends);
    }

    public function test_password_is_hidden_from_serialization(): void {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_email_verified_at_is_hidden_from_serialization(): void {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('email_verified_at', $array);
    }
}