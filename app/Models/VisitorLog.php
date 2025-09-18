<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    protected $fillable = [
    'email',
    'otp',
    'full_name',
    'address',
    'mobile',
    'check_in_at',
    'check_out_at',
    'visited_user_id',
    'purpose',
    'status',
    'meetup_spot',
    'company',
];

    public function visitedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'visited_user_id');
    }

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];
}

