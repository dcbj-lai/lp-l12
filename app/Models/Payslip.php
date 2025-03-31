<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payout_id',
        'basic_pay',
        'total_additions',
        'total_deductions',
        'tax_withheld',
        'net_pay',
        'adjustments',
    ];

    protected $casts = [
        'adjustments' => 'array', // Automatically cast the JSON adjustments to an array
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Payout (Cycle)
    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}
