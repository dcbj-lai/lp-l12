<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreEnrollmentMedicalClearance extends Model
{
    public const STATUS_CLEARED = 'cleared';
    public const STATUS_PENDING = 'pending_requirements';
    public const STATUS_NOT_CLEARED = 'not_cleared';

    protected $fillable = [
        'applicant_name',
        'email',
        'contact_number',
        'intended_course',
        'assessment_date',
        'clearance_status',
        'findings',
        'recommendations',
        'issued_by_id',
        'issued_by_name',
        'issued_at',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'issued_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_CLEARED => 'Cleared for enrollment',
            self::STATUS_PENDING => 'Pending requirements',
            self::STATUS_NOT_CLEARED => 'Not cleared',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->clearance_status] ?? ucfirst(str_replace('_', ' ', $this->clearance_status));
    }

    public function signatoryName(): string
    {
        return $this->issuedBy?->name ?: ($this->issued_by_name ?: '-');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }
}
