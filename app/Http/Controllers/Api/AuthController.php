<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request) {
        $request->validate([
            'username' => 'required|string|max:30|alpha_num|unique:users',
            'email'     => 'required|string|email|unique:users',
            'password'  => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please check your email to verify your account.',
            'user' => [
                'id'       => $user->id,
                'username' => $user->username,
            ],
        ], 201);
    }

    public function login(Request $request) {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect'],
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success'    => false,
                'unverified' => true,
                'message'    => 'Please verify your email before logging in.',
            ], 403);
        }

        Auth::login($user);

        return response()->json([
            'success' => true,
            'user' => [
                'id'       => $user->id,
                'username' => $user->username,
            ],
        ]);
    }

    public function logout(Request $request) {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out',
        ]);
    }

    public function user(Request $request) {
        $user = $request->user();

        return response()->json([
            'id'              => $user->id,
            'username'        => $user->username,
            'email'           => $user->email,
            'bio'             => $user->bio,
            'profile_picture' => $user->profile_picture,
        ]);
    }
}