<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityReservationCancellation extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['resolved_at' => 'datetime'];
}
