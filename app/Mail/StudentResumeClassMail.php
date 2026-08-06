<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentResumeClassMail extends Mailable
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
        return $this->subject("Guidance and Wellness Session Attendance -  *CONFIDENTIALITY NOTICE")
            ->view('emails.guidance.student_resume_class');
    }
}
