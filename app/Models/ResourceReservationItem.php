<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResourceReservationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'resource_id',
    ];

    public function reservation()
    {
        return $this->belongsTo(ResourceReservation::class, 'reservation_id');
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
