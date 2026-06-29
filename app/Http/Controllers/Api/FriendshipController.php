<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use Illuminate\Http\Request;
use App\Models\User;

class FriendshipController extends Controller {

    public function sendRequest(Request $request) {
        $request->validate([
            'friend_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ((int) $value === $request->user()->id) {
                        $fail('You cannot send a friend request to yourself.');
                    }
                },
            ],
        ]);

        $alreadyExists = $request->user()->sentFriendRequests()
                ->where('friend_id', $request->friend_id)->exists()
            || $request->user()->receivedFriendRequests()
                ->where('user_id', $request->friend_id)->exists();

        if ($alreadyExists) {
            return response()->json([
                'success' => false,
                'message' => 'A friendship or request already exists between these users.',
            ], 409);
        }

        $friendship = $request->user()->sentFriendRequests()->create([
            'friend_id' => $request->friend_id,
            'status'    => 'pending',
        ]);

        return response()->json([
            'success'    => true,
            'friendship' => $friendship,
        ], 201);
    }

    public function respondRequest(Request $request) {
        $request->validate([
            'friendship_id' => 'required|integer|exists:friendships,id',
            'status'        => 'required|in:accepted,rejected',
        ]);

        $friendship = $request->user()->receivedFriendRequests()
            ->find($request->friendship_id);

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to respond to this request.',
            ], 403);
        }

        $friendship->status = $request->status;
        $friendship->save();

        return response()->json([
            'success'    => true,
            'friendship' => $friendship,
        ]);
    }

    public function pendingRequests(Request $request) {
        $requests = $request->user()->receivedFriendRequests()
            ->where('status', 'pending')
            ->with('sender')
            ->get();

        return response()->json([
            'success'  => true,
            'requests' => $requests,
        ]);
    }

    public function retrieveFriends(Request $request) {
        $userId = $request->user()->id;

        $friendships = Friendship::where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('friend_id', $userId);
            })
            ->with(['sender', 'recipient'])
            ->get()
            ->map(function ($friendship) use ($userId) {
                $friendship->friend = $friendship->user_id === $userId
                    ? $friendship->recipient
                    : $friendship->sender;
                return $friendship;
            });

        return response()->json([
            'success' => true,
            'friends' => $friendships,
        ]);
    }

    public function searchValidUsers(Request $request) {
        $request->validate([
            'username' => 'required|string|min:1',
        ]);

        $users = User::where('username', 'like', '%' . $request->username . '%')
            ->where('id', '!=', $request->user()->id)
            ->limit(10)
            ->get(['id', 'username']);

        return response()->json([
            'success' => true,
            'users'   => $users,
        ]);
    }

    public function removeFriend(Request $request, $id) {
        $friendship = $request->user()->sentFriendRequests()->find($id)
            ?? $request->user()->receivedFriendRequests()->find($id);

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'Friendship not found.',
            ], 404);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Friend removed.',
        ]);
    }
}