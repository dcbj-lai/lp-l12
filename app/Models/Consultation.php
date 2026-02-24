<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = [
        'client_id',
        'time_in',
        'time_out',
        'type_of_session',
        'risk_assessment',
        'issue_concern',
        'intervention',
        'remarks',
    ];

    protected $casts = [
        'time_in'  => 'datetime',
        'time_out' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}