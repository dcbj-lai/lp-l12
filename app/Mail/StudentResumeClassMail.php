<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentResumeClassMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $studentName;
    public ?string $teacherName;
    public ?string $timeInDisplay;
    public ?string $timeOutDisplay;

    public function __construct(
        string $studentName,
        ?string $teacherName,
        ?string $timeInDisplay,
        ?string $timeOutDisplay
    ) {
        $this->studentName = $studentName;
        $this->teacherName = $teacherName;
        $this->timeInDisplay = $timeInDisplay;
        $this->timeOutDisplay = $timeOutDisplay;
    }

    public function build()
    {
        return $this->subject("H&W-Guidance: {$this->studentName} Returning to Class")
            ->view('emails.guidance.student_resume_class');
    }
}