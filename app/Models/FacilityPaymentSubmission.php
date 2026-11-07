<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityPaymentSubmission extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['files' => 'array', 'paid_on' => 'date', 'reviewed_at' => 'datetime'];

    public function reservation()
    {
        return $this->belongsTo(ResourceReservation::class)->withTrashed();
    }
}
