<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class FacilityReservationCancellationMail extends Mailable
{
    use Queueable;

    public function __construct(public array $details) {}

    public function build(): self
    {
        $label = match ($this->details['status']) {
            'review' => 'Cancellation requested — facilities review required',
            'declined' => 'Cancellation request declined',
            default => 'Reservation cancelled',
        };
        return $this->subject($label.': '.$this->details['title'])->view('emails.resource-booking.cancelled', ['label' => $label]);
    }
}
