<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'guest_count',
        'shirt_size',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'guest_count' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isAttending(): bool
    {
        return $this->status === 'attending';
    }
}
