<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayCampaign extends Model
{
    protected $fillable = [
        'html',
        'assets',
        'subject',
        'from_email',
        'from_name',
    ];

    protected $casts = [
        'assets' => 'array',
    ];
}
