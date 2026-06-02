<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    public const MAX_CUSTOM_FIELDS = 3;

    protected $fillable = [
        'title',
        'description',
        'location',
        'start_datetime',
        'end_datetime',
        'rsvp_deadline',
        'status',
        'custom_field_labels',
        'created_by',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'rsvp_deadline' => 'datetime',
        'custom_field_labels' => 'array',
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
            ->withPivot(['status', 'guest_count', 'shirt_size', 'custom_field_answers', 'responded_at'])
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

    public static function normalizeCustomFieldLabels(?array $labels): array
    {
        $normalized = [];

        for ($index = 0; $index < self::MAX_CUSTOM_FIELDS; $index++) {
            $normalized[$index] = trim((string) ($labels[$index] ?? ''));
        }

        return $normalized;
    }

    public function customFieldLabels(): array
    {
        return array_filter(
            self::normalizeCustomFieldLabels($this->custom_field_labels ?? []),
            fn (string $label) => $label !== ''
        );
    }

    public function hasCustomFields(): bool
    {
        return $this->customFieldLabels() !== [];
    }
}
