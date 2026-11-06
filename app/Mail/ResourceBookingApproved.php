<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResourceBookingApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use \App\Mail\Concerns\HasFacilityRequesterActions;

    public function __construct(public $reservation)
    {
    }

    public function build()
    {
        return $this->subject('Your Booking is Approved')
            ->view('emails.resource-booking.requester-approved', ['requesterActions' => $this->requesterActionData($this->reservation->id)]);
    }
}
