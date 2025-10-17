<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'is_active',
        'declared_by',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];
}
