<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class StudentCheckedInNoTeacherMail extends Mailable
{
    public function __construct(
        public string $studentName,
        public string $timeInDisplay
    ) {}

    public function build()
    {
        return $this->subject("H&W-Guidance: Student {$this->studentName} Check-In (No Teacher Assigned)")
            ->text('emails.guidance.student-checked-in-no-teacher');
    }
}