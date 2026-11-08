<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityPaymentSubmission extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['files' => 'array', 'paid_on' => 'date', 'reviewed_at' => 'datetime', 'previous_paid_on' => 'date', 'previous_proof_ids' => 'array'];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reservation()
    {
        return $this->belongsTo(ResourceReservation::class)->withTrashed();
    }
}
