<?php

namespace App\Mail;

use App\Models\VisitorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class VisitorApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $visitor;

    public function __construct(VisitorLog $visitor)
    {
        $this->visitor = $visitor;
    }

    public function build()
    {
        return $this->markdown('emails.visitor.approved')
                    ->subject("Visitor Approved: {$this->visitor->full_name}");
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Visitor Approved Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.visitor.approved',
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
