<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class StudentGoHomeMail extends Mailable
{
    public function __construct(
        public string $studentName,
        public string $teacherName,
        public string $timeOutDisplay,
        public string $releaseMode,
        public ?string $releaseDetails = null
    ) {}

    public function build()
    {
        return $this->subject("Student Released from Campus – {$this->studentName}")
            ->text('emails.guidance.student-go-home');
    }
}