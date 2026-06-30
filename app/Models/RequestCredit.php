<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pto',
        'wfh',
        'offset',
        'approved_carry_over',
    ];

    protected function casts(): array
    {
        return [
            'pto' => 'decimal:2',
            'wfh' => 'decimal:2',
            'approved_carry_over' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
