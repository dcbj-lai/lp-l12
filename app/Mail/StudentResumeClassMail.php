<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class StudentResumeClassMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $teacherName,
        public string $timeOutDisplay
    ) {}

    public function build()
    {
        return $this->subject("H&W: Student Returning to Class – {$this->studentName}")
            ->text('emails.guidance.student-resume-class');
    }
}