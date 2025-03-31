<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Adjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'mode',
        'description',
        'user_id',
        'cycle',
        'amount',
        'effective_date',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecurring($query)
    {
        return $query->where('effective_date', '9999-12-31');
    }

    public function scopeEffectiveForCycle($query, $payPeriodStart, $payPeriodEnd)
    {
        return $query->whereBetween('effective_date', [$payPeriodStart, $payPeriodEnd])
                     ->orWhere('effective_date', '9999-12-31');
    }
}
