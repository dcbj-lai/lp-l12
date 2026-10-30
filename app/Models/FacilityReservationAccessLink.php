<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityReservationAccessLink extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['token_hash', 'recipient_hash'];
    protected $casts = ['expires_at' => 'datetime'];
}
