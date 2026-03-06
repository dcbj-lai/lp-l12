<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'course',
        'section',
    ];

    // Optional convenience accessor
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function consultations()
{
    return $this->hasMany(\App\Models\Consultation::class);
}
}