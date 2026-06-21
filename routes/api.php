<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\ProfileController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/sessions', [SessionController::class, 'store']);
    Route::get('/sessions', [SessionController::class, 'index']);
    Route::get('/friends', [FriendshipController::class, 'retrieveFriends']);
    Route::post('/friends/request', [FriendshipController::class, 'sendRequest']);
    Route::post('/friends/respond', [FriendshipController::class, 'respondRequest']);
    Route::get('/friends/pending', [FriendshipController::class, 'pendingRequests']);
    Route::get('/friends/search', [FriendshipController::class, 'searchValidUsers']);
    Route::delete('/friends/{id}', [FriendshipController::class, 'removeFriend']);
    Route::post('/profile/bio', [ProfileController::class, 'updateBio']);
    Route::post('/profile/picture', [ProfileController::class, 'uploadProfilePicture']);
    Route::patch('/profile/username', [ProfileController::class, 'updateUsername']);
});