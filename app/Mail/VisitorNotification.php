<?php

namespace App\Mail;

use App\Models\VisitorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class VisitorNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $visitor;

    public function __construct(VisitorLog $visitor)
    {
        $this->visitor = $visitor;
    }

    public function build()
    {
        return $this->markdown('emails.visitor.notification')
                    ->subject('New Visitor Checked In');
    }
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Visitor Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.visitor.notification',
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
