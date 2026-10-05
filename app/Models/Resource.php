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
        'floor',
        'capacity',
        'created_by',
        'image_path',
        'control_number',
        'total_quantity',
    ];

    protected $casts = ['total_quantity' => 'integer'];

    public function floorLabel(): string
    {
        return $this->floor ?: 'Floor not set';
    }

    public function floorSortKey(): int
    {
        if (preg_match('/^Ground/i', (string) $this->floor)) {
            return 0;
        }
        if (preg_match('/^(\d+)/', (string) $this->floor, $matches)) {
            return (int) $matches[1];
        }

        return 999;
    }

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
