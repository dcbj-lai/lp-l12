<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentGoHomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $studentName;
    public ?string $teacherName;
    public ?string $timeInDisplay;
    public ?string $timeOutDisplay;
    public ?string $goingHomeMethod;
    public ?string $fetcherName;
    public ?string $selfApprovedBy;

    public function __construct(
        string $studentName,
        ?string $teacherName,
        ?string $timeInDisplay,
        ?string $timeOutDisplay,
        ?string $goingHomeMethod,
        ?string $fetcherName,
        ?string $selfApprovedBy
    ) {
        $this->studentName = $studentName;
        $this->teacherName = $teacherName;
        $this->timeInDisplay = $timeInDisplay;
        $this->timeOutDisplay = $timeOutDisplay;
        $this->goingHomeMethod = $goingHomeMethod;
        $this->fetcherName = $fetcherName;
        $this->selfApprovedBy = $selfApprovedBy;
    }

    public function build()
    {
        return $this->subject("Guidance and Wellness Session Attendance -  *CONFIDENTIALITY NOTICE")
            ->text('emails.guidance.student_go_home');
    }
}