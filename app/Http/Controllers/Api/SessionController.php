<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClimbingSession;
use Illuminate\Http\Request;

class SessionController extends Controller {
    public function store(Request $request) {
        $request->validate([
            'place'              => 'required|string|max:255',
            'date'               => 'required|date',
            'time_start'         => 'required|date_format:H:i',
            'time_end'           => 'nullable|date_format:H:i',
            'needs_tr_belayer'   => 'boolean',
            'needs_lead_belayer' => 'boolean',
            'other_need'         => 'nullable|string|max:255',
            'notes'              => 'nullable|string',
        ]);

         $session = ClimbingSession::create([
            'user_id'            => $request->user()->id,
            'place'              => $request->place,
            'date'               => $request->date,
            'time_start'         => $request->time_start,
            'time_end'           => $request->time_end,
            'needs_tr_belayer'   => $request->needs_tr_belayer ?? false,
            'needs_lead_belayer' => $request->needs_lead_belayer ?? false,
            'other_need'         => $request->other_need,
            'notes'              => $request->notes,
         ]);

        return response()->json([
            'success' => true,
            'session' => $session,
        ], 201);
    }
}
