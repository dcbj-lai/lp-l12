<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Patient extends Model
{
    protected $table = 'patients';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'type',
        'course',
        'is_under_accessibility',
        'department',
        'position',
        'emergency_contact_person',
        'emergency_contact_number',
        'blood_type',
    ];

    protected $casts = [
        'is_under_accessibility' => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isStudent(): bool
    {
        return $this->type === 'student';
    }

    public function isStaff(): bool
    {
        return $this->type === 'staff';
    }

    public function isUnderAccessibility(): bool
    {
        return $this->isStudent() && $this->is_under_accessibility;
    }

    public function index()
    {
    $students = Patient::where('type', 'student')->paginate(10, ['*'], 'students_page');
    $staff = Patient::where('type', 'staff')->paginate(10, ['*'], 'staff_page');

    return view('clinic.patients.index', compact('students', 'staff'));
    }

    public function clinicConsultations()
    {
    return $this->hasMany(\App\Models\ClinicConsultation::class);
    }

    public function photoAttachmentsFolder(): string
{
    $safe = Str::slug(trim("{$this->first_name} {$this->last_name}"), '-');
    return "clinic/photo-attachments/{$this->id}-{$safe}";
}
}
