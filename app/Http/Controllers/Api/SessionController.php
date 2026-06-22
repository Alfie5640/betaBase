<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClimbingSession;
use App\Models\Friendship;
use Illuminate\Http\Request;

class SessionController extends Controller {
    public function index(Request $request) {
        $friendIds = $request->user()->friends()->pluck('id')->push($request->user()->id);

        $sessions = ClimbingSession::whereIn('user_id', $friendIds)
        ->with('user:id,username,profile_picture')
        ->orderBy('date')
        ->get();

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'place'              => 'required|string|max:255',
            'date'               => 'required|date',
            'time_start'         => 'required|date_format:H:i',
            'time_end'           => 'nullable|date_format:H:i',
            'needs_tr_belayer'   => 'boolean',
            'needs_lead_belayer' => 'boolean',
            'other_need'         => 'nullable|string|max:255',
            'notes'              => 'nullable|string',
        ]);

        $session = $request->user()->climbingSessions()->create($validated);

        return response()->json([
            'success' => true,
            'session' => $session,
        ], 201);
    }
}
