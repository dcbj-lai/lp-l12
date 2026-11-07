<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacilityPaymentReviewMail extends Mailable
{
    use Queueable, SerializesModels;
    use \App\Mail\Concerns\HasFacilityRequesterActions;

    public function __construct(public string $title, public string $messageText, public string $actionUrl, public string $actionLabel, public ?int $reservationId = null) {}

    public function build(): self
    {
        return $this->subject($this->title)->view('emails.resource-booking.payment-review', ['requesterActions' => $this->requesterActionData($this->reservationId)]);
    }
}
