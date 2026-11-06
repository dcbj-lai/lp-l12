<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityPaymentReceived extends Mailable
{
    use Queueable, SerializesModels;
    use \App\Mail\Concerns\HasFacilityRequesterActions;

    public function __construct(public array $details) {}

    public function build(): self
    {
        return $this->subject('Payment received: '.$this->details['title'])
            ->view('emails.resource-booking.payment-received', ['requesterActions' => $this->requesterActionData($this->details['reservation_id'] ?? null)]);
    }
}
