<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['username', 'email', 'password', 'bio'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function climbingSessions() {
        return $this->hasMany(ClimbingSession::class);
    }

    public function sentFriendRequests() {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function receivedFriendRequests() {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    public function friends() {
        $friendships = Friendship::where('status', 'accepted')
            ->where(function ($query) {
                $query->where('user_id', $this->id)
                    ->orWhere('friend_id', $this->id);
            })
            ->get();

        $friendIds = $friendships->map(function ($friendship) {
            return $friendship->user_id == $this->id
                ? $friendship->friend_id
                : $friendship->user_id;
        });

        return User::whereIn('id', $friendIds)->get();
    }
}
