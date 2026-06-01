<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'start_datetime',
        'end_datetime',
        'rsvp_deadline',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'rsvp_deadline' => 'datetime',
    ];

    // 🔗 Creator (PNC/HR)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔗 Admin instruction attachments (S3)
    public function attachments()
    {
        return $this->hasMany(EventAttachment::class);
    }

    // 🔗 RSVPs
    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    // 🔗 Attending RSVPs only
    public function attendingRegistrations()
    {
        return $this->hasMany(EventRegistration::class)->where('status', 'attending');
    }

    // 🔗 Users via registrations
    public function registrants()
    {
        return $this->belongsToMany(User::class, 'event_registrations')
            ->withPivot(['status', 'guest_count', 'shirt_size', 'responded_at'])
            ->withTimestamps();
    }

    // 💡 Helpers
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function rsvpClosed(): bool
    {
        return $this->rsvp_deadline !== null && $this->rsvp_deadline->isPast();
    }
}
