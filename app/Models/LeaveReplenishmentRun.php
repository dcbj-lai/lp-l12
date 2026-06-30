<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveReplenishmentRun extends Model
{
    protected $fillable = [
        'run_date',
        'pto_default',
        'wfh_default',
        'users_count',
        'total_approved_carry_over',
        'run_by',
    ];

    protected function casts(): array
    {
        return [
            'run_date' => 'date',
            'pto_default' => 'decimal:2',
            'wfh_default' => 'decimal:2',
            'users_count' => 'integer',
            'total_approved_carry_over' => 'decimal:2',
        ];
    }

    public function runner()
    {
        return $this->belongsTo(User::class, 'run_by');
    }
}
