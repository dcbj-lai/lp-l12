<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityGeneratedSoa extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['snapshot' => 'array', 'issue_date' => 'date', 'due_date' => 'date', 'sent_at' => 'datetime'];
}
