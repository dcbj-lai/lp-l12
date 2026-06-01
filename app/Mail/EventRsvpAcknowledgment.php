<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventRsvpAcknowledgment extends Mailable
{
    use Queueable, SerializesModels;

    public EventRegistration $registration;

    public function __construct(EventRegistration $registration)
    {
        $this->registration = $registration;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your RSVP — ' . ($this->registration->event->title ?? 'Event'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.event.rsvp-acknowledgment',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
