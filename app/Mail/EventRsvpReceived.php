<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventRsvpReceived extends Mailable
{
    use Queueable, SerializesModels;

    public EventRegistration $registration;

    public function __construct(EventRegistration $registration)
    {
        $this->registration = $registration;
    }

    public function envelope(): Envelope
    {
        $name = $this->registration->user->name ?? 'A staff member';

        return new Envelope(
            subject: "Event RSVP: {$name} — " . ($this->registration->event->title ?? 'Event'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.event.rsvp-received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
