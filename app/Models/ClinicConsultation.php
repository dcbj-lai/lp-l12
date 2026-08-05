<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicConsultation extends Model
{
    public const EMAIL_STATUS_QUEUED = 'queued';

    public const EMAIL_STATUS_SENT = 'sent';

    public const EMAIL_STATUS_FAILED = 'failed';

    protected $table = 'clinic_consultations';

    protected $fillable = [
        'patient_id',
        'time_in',
        'time_out',
        'chief_complaint',
        'case_classification',

        // vitals
        'blood_pressure',
        'pulse_rate',
        'respiratory_rate',
        'temperature',
        'o2_saturation',

        // pain rating
        'pain_rating',

        'assessment',
        'treatment',
        'medicines',
        'supplies',
        'remarks',

        // photos
        'photo_attachments',

        // teacher / student workflow
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
    ];

    protected $casts = [
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'photo_attachments' => 'array',
        'pain_rating' => 'integer',
        'medicines' => 'array',
        'supplies' => 'array',
        'email_sent_at' => 'datetime',
        'email_failed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
