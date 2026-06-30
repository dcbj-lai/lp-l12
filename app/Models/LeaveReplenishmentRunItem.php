<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveReplenishmentRunItem extends Model
{
    protected $fillable = [
        'leave_replenishment_run_id',
        'user_id',
        'employee_number',
        'employee_name',
        'employee_email',
        'previous_pto',
        'previous_wfh',
        'pto_default',
        'wfh_default',
        'approved_carry_over_applied',
        'initialized_pto',
        'initialized_wfh',
    ];

    protected function casts(): array
    {
        return [
            'previous_pto' => 'decimal:2',
            'previous_wfh' => 'decimal:2',
            'pto_default' => 'decimal:2',
            'wfh_default' => 'decimal:2',
            'approved_carry_over_applied' => 'decimal:2',
            'initialized_pto' => 'decimal:2',
            'initialized_wfh' => 'decimal:2',
        ];
    }

    public function run()
    {
        return $this->belongsTo(LeaveReplenishmentRun::class, 'leave_replenishment_run_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
