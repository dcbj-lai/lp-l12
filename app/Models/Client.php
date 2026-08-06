<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'course',
        'section',
        'is_under_accessibility',
        'emergency_contact_person',
        'emergency_contact_number',
        'blood_type',
    ];

    protected $casts = [
        'is_under_accessibility' => 'boolean',
    ];

    // Optional convenience accessor
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isUnderAccessibility(): bool
    {
        return (bool) $this->is_under_accessibility;
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }
}
