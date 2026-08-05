<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentGoHomeClinicMail extends Mailable
{
    use SerializesModels;

    public string $studentName;
    public ?string $teacherName;
    public ?string $dateDisplay;
    public ?string $timeInDisplay;
    public ?string $timeOutDisplay;
    public ?string $goingHomeMethod;
    public ?string $fetcherName;
    public ?string $selfApprovedBy;

    public function __construct(
        string $studentName,
        ?string $teacherName,
        ?string $dateDisplay,
        ?string $timeInDisplay,
        ?string $timeOutDisplay,
        ?string $goingHomeMethod,
        ?string $fetcherName,
        ?string $selfApprovedBy
    ) {
        $this->studentName = $studentName;
        $this->teacherName = $teacherName;
        $this->dateDisplay = $dateDisplay;
        $this->timeInDisplay = $timeInDisplay;
        $this->timeOutDisplay = $timeOutDisplay;
        $this->goingHomeMethod = $goingHomeMethod;
        $this->fetcherName = $fetcherName;
        $this->selfApprovedBy = $selfApprovedBy;
    }

    public function build()
    {
        return $this->subject("Clinic Consultation Attendance - *CONFIDENTIALITY NOTICE")
            ->view('emails.clinic.student_go_home');
    }
}
