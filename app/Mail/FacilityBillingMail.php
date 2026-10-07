<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class FacilityBillingMail extends Mailable
{
    public function __construct(public array $details, public string $kind)
    {
    }

    public function build(): self
    {
        $prefix = match ($this->kind) {
            'soa' => 'Statement of Account',
            'updated_soa' => 'Updated Statement of Account',
            'overdue' => 'Overdue payment reminder',
            'due_today' => 'Payment due today',
            default => 'Payment reminder',
        };

        return $this->subject($prefix . ': ' . $this->details['title'])
            ->view('emails.resource-booking.billing')
            ->attachFromStorageDisk($this->details['disk'], $this->details['attachment'], 'Statement-of-Account.' . pathinfo($this->details['attachment'], PATHINFO_EXTENSION));
    }
}
