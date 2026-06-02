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
        'custom_field_answers',
        'shirt_size',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'guest_count' => 'integer',
        'custom_field_answers' => 'array',
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

    public static function normalizeCustomFieldAnswers(?array $answers): array
    {
        $normalized = [];

        for ($index = 0; $index < Event::MAX_CUSTOM_FIELDS; $index++) {
            $normalized[$index] = trim((string) ($answers[$index] ?? ''));
        }

        return $normalized;
    }

    public function customFieldAnswers(): array
    {
        return self::normalizeCustomFieldAnswers($this->custom_field_answers ?? []);
    }

    public function customFieldAnswer(int $index): string
    {
        return $this->customFieldAnswers()[$index] ?? '';
    }
}
