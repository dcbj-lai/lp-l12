<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityPaymentProof extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['paid_on' => 'date'];
}
