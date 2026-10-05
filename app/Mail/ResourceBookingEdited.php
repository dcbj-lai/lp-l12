<?php

namespace App\Mail;

use App\Models\ResourceReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResourceBookingEdited extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ResourceReservation $reservation, public array $changes)
    {
    }

    public function build(): self
    {
        return $this->subject('Your reservation details were updated: ' . $this->reservation->title)
            ->view('emails.resource-booking.requester-edited');
    }
}
