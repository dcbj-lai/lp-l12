<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    protected $fillable = [
        'email', 'mobile','otp', 
        'full_name', 'address', 
        'check_in_at', 'visited_user_id','purpose','check_out_at'
    ];
}

