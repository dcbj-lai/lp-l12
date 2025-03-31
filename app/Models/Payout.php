<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'control_number',
        'pay_period_start',
        'pay_period_end',
        'payout_date',
        'dispatched_date',
        'total_amount',
        'status',
        'cycle',
    ];

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope for pending payouts
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope for dispatched payouts
    public function scopeCompleted($query)
    {
        return $query->where('status', 'dispatched');
    }

    // Helper method to format payout amount
    public function formattedAmount()
    {
        return number_format($this->total_amount, 2);
    }

    // Generate next control number (e.g., YYYYMMDD-001)
    public static function generateControlNumber()
{
    $year = now()->format('Y');

    return DB::transaction(function () use ($year) {
        $lastPayout = self::whereYear('created_at', now()->year)
            ->orderBy('control_number', 'desc')
            ->lockForUpdate()
            ->first();

        $nextNumber = $lastPayout
            ? (int)substr($lastPayout->control_number, -3) + 1
            : 1;

        return $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    });
}

}
