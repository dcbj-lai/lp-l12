<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentResumeClassClinicMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $studentName;
    public ?string $teacherName;
    public ?string $dateDisplay;
    public ?string $timeInDisplay;
    public ?string $timeOutDisplay;

    public function __construct(
        string $studentName,
        ?string $teacherName,
        ?string $dateDisplay,
        ?string $timeInDisplay,
        ?string $timeOutDisplay
    ) {
        $this->studentName = $studentName;
        $this->teacherName = $teacherName;
        $this->dateDisplay = $dateDisplay;
        $this->timeInDisplay = $timeInDisplay;
        $this->timeOutDisplay = $timeOutDisplay;
    }

    public function build()
    {
        return $this->subject("H&W-Clinic Session Attendance - *CONFIDENTIALITY NOTICE")
            ->view('emails.clinic.student_resume_class');
    }
}