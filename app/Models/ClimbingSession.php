<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'place', 'date', 'time_start', 'time_end', 'needs_tr_belayer', 'needs_lead_belayer', 'other_need', 'notes'])]
class ClimbingSession extends Model {
    //
}
