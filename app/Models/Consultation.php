<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consultation extends Model
{
    use SoftDeletes;

    public const EMAIL_STATUS_QUEUED = 'queued';

    public const EMAIL_STATUS_SENT = 'sent';

    public const EMAIL_STATUS_FAILED = 'failed';

    protected $fillable = [
        'client_id',

        // time tracking
        'time_in',
        'time_out',

        // teacher + outcome
        'check_in_teacher',
        'check_in_teacher_email',
        'current_teacher',
        'teacher_email',
        'after_consultation',
        'going_home_method',
        'fetcher_name',
        'self_approved_by',
        'email_status',
        'email_sent_at',
        'email_failed_at',
        'email_failure_message',

        // consultation details
        'type_of_session',
        'risk_assessment',
        'issue_concern',
        'intervention',
        'remarks',
    ];

    protected $casts = [
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'email_sent_at' => 'datetime',
        'email_failed_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
