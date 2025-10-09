<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VisitorLog extends Model
{
    protected $fillable = [
        'email',
        'mobile',
        'otp',
        'full_name',
        'address',
        'purpose',
        'status',
        'meetup_spot',
        'visited_user_id',
        'check_in_at',
        'check_out_at',
        'batch_id',
        'visit_date',
        'company',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'visit_date' => 'datetime',
    ];

    public function visitedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'visited_user_id');
    }

    public static function generateBatchId(): string
    {
        return (string) Str::uuid();
    }
}
