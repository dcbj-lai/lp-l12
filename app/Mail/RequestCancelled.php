<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use App\Models\Request as StaffRequest;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class RequestCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public $request;

    public function __construct(StaffRequest $request)
    {
        $this->request = $request;
    }

public function build()
{
    $typeLabel = $this->request->type === 'PTO'
        ? 'Leave'
        : ($this->request->type === 'WFH'
            ? 'Work from home'
            : strtolower($this->request->type));

    return $this->subject('Request Cancelled: ' . ucfirst($typeLabel))
                ->markdown('emails.request.cancelled');
}




}
