<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentResumeClassMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $studentName;
    public string $teacherName;
    public string $timeOutDisplay;

    public function __construct(
        string $studentName,
        string $teacherName,
        string $timeOutDisplay
    ) {
        $this->studentName    = $studentName;
        $this->teacherName    = $teacherName;
        $this->timeOutDisplay = $timeOutDisplay;
    }

    public function build()
    {
        return $this->subject("Student Returning to Class – {$this->studentName}")
            ->text('emails.guidance.student-resume-class');
    }
}