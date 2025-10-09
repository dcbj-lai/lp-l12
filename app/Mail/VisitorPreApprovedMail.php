<?php

namespace App\Mail;

use App\Models\VisitorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VisitorPreApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $visitor;
    public $pathToImage;
    public $qrImage;

    public function __construct(VisitorLog $visitor)
    {
        $this->visitor = $visitor;

        // Generate QR code
        $qrPng = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate(config('app.url') . "/visitors/{$visitor->id}/verify/{$visitor->batch_id}");



        $directory = storage_path('app/public/qr');
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $this->pathToImage = "{$directory}/qr_{$visitor->id}.png";
        file_put_contents($this->pathToImage, $qrPng);

    }

    public function build()
    {
        return $this->subject('Your Visit with LAIC')
            ->view('emails.visitors.preapproved')
            ->with([
                'visitor' => $this->visitor,
                'qrImage' => $this->qrImage,
            ]);
    }

    /**
     * Laravel 12-style attachments
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pathToImage)
                ->as('visitor_qr.png')
                ->withMime('image/png'),
        ];
    }
}
