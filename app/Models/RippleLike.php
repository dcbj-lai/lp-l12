<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RippleLike extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'ripple_id'];

    public function ripple()
    {
        return $this->belongsTo(Ripple::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
