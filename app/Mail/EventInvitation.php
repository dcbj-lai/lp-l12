<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public ?string $inviteeName;

    public function __construct(Event $event, ?string $inviteeName = null)
    {
        $this->event = $event;
        $this->inviteeName = $inviteeName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited: " . $this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.event.invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
