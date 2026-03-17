<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentCheckedInMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $studentName;
    public string $teacherName;
    public string $timeInDisplay;

    public function __construct(
        string $studentName,
        string $teacherName,
        string $timeInDisplay
    ) {
        $this->studentName   = $studentName;
        $this->teacherName   = $teacherName;
        $this->timeInDisplay = $timeInDisplay;
    }

    public function build()
    {
        return $this->subject("H&W-Guidance: Student {$this->studentName} Check-In")
            ->text('emails.guidance.student-checked-in');
    }
}