<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    protected $fillable = ['user_id', 'steps', 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'date' => 'date',
    ];
    
}

