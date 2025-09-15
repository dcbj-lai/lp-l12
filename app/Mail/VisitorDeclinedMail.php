<?php

namespace App\Mail;

use App\Models\VisitorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class VisitorDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public VisitorLog $visitor;

    /**
     * Create a new message instance.
     */
    public function __construct(VisitorLog $visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->subject("Visitor Visit Declined: {$this->visitor->full_name}")
            ->markdown('emails.visitor.declined');
    }
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Visitor Declined Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.visitor.declined',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
