<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'approver_id',
        'type',
        'is_offset',
        'reason',
        'start_date',
        'end_date',
        'end_date_type',
        'number_of_days',
        'status',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}

