<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityBillingEmail extends Model
{
    protected $guarded = [];

    protected $casts = ['snapshot' => 'array', 'sent_at' => 'datetime'];
}
