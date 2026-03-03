<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consultation extends Model
{
    protected $fillable = [
        'client_id',

        // time tracking
        'time_in',
        'time_out',

        // teacher + outcome
        'current_teacher',
        'after_consultation',
        'going_home_method',
        'fetcher_name',
        'self_approved_by',

        // consultation details
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

        use SoftDeletes;
        protected $dates = ['deleted_at'];

}