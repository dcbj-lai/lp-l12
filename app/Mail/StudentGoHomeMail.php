<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class StudentGoHomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $teacherName,
        public string $timeOutDisplay,
        public string $releaseMode,
        public ?string $releaseDetails = null
    ) {}

    public function build()
    {
        return $this->subject("H&W: Student Released from Campus – {$this->studentName}")
            ->text('emails.guidance.student-go-home');
    }
}