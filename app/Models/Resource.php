<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'location',
        'capacity',
        'created_by',
        'image_path',
    ];

    // 🔗 Who created it
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔗 Reservations where this is the PRIMARY resource
    public function reservations()
    {
        return $this->hasMany(ResourceReservation::class);
    }

    // 🔗 Reservations where this is used as equipment
    public function reservationItems()
    {
        return $this->hasMany(ResourceReservationItem::class);
    }

    // 💡 Helper
    public function isRoom(): bool
    {
        return $this->type === 'room';
    }

    public function isEquipment(): bool
    {
        return $this->type === 'equipment';
    }
}
