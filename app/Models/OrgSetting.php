<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgSetting extends Model
{
    protected $fillable = ['pto_default', 'wfh_default', 'last_leave_replenished_on'];

    protected function casts(): array
    {
        return [
            'pto_default' => 'decimal:2',
            'wfh_default' => 'decimal:2',
            'last_leave_replenished_on' => 'date',
        ];
    }
}
