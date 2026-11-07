<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityPaymentOverride extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['previous_paid_on' => 'date', 'paid_on' => 'date', 'previous_proof_ids' => 'array', 'replacement_proof_ids' => 'array', 'submission_ids' => 'array'];
    public function actor() { return $this->belongsTo(User::class, 'overridden_by'); }
}
