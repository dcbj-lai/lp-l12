<?php

namespace App\Mail;

use App\Models\ResourceReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResourceBookingRequesterConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ResourceReservation $reservation;

    public function __construct(ResourceReservation $reservation)
    {
        $this->reservation = $reservation->load('resource', 'equipment');
    }

    public function build()
    {
        return $this->subject('Your Booking Request Has Been Received')
            ->view('emails.resource-booking.requester');
    }
}
