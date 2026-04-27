<?php

namespace App\Mail;

use App\Models\ResourceReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResourceBookingAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public ResourceReservation $reservation;

    public function __construct(ResourceReservation $reservation)
    {
        $this->reservation = $reservation->load('resource', 'equipment');
    }

    public function build()
    {
        return $this->subject('New Resource Booking Request')
            ->view('emails.resource-booking.admin')
            ->with([
                'url' => route('resources.index'), // 👈 FIX
            ]);
    }
}
