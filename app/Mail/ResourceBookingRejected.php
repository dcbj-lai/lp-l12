<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResourceBookingRejected extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public $reservation)
    {
    }

    public function build()
    {
        return $this->subject('Your Booking was Rejected')
            ->view('emails.resource-booking.requester-rejected');
    }
}
