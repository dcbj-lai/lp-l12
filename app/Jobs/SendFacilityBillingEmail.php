<?php

namespace App\Jobs;

use App\Mail\FacilityBillingMail;
use App\Models\FacilityBillingEmail;
use App\Models\ResourceReservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendFacilityBillingEmail implements ShouldQueue
{
    use Queueable;

    // Avoid automatic retries sending duplicate billing messages after a transport timeout.
    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $message = FacilityBillingEmail::findOrFail($this->messageId);
            $reservation = ResourceReservation::whereKey($message->reservation_id)->lockForUpdate()->first();
            $message = FacilityBillingEmail::whereKey($this->messageId)->lockForUpdate()->firstOrFail();
            if ($message->status !== 'queued') { return; }
            $details = $message->snapshot;
            $isSoa = in_array($message->kind, ['soa', 'updated_soa']);
            if (!$reservation || $reservation->soa_locked || $reservation->status !== 'approved' || $reservation->soa_path !== $details['soa_path'] || $reservation->requester_email !== $details['recipient'] || (!$isSoa && ($reservation->billing_status !== 'billed' || !$reservation->payment_due_at || $reservation->soa_email_pending))) {
                $message->update(['status' => 'skipped']);
                return;
            }
            if ($message->kind === 'soa') {
                $details['due_date'] = ($reservation->payment_due_at ?? today()->addDays(15))->format('M j, Y');
            }
            if (!$isSoa) {
                $message->kind = $reservation->payment_due_at->isBefore(today()) ? 'overdue' : ($reservation->payment_due_at->isSameDay(today()) ? 'due_today' : 'upcoming');
                $details['due_date'] = $reservation->payment_due_at->format('M j, Y');
            }
            $details['paid'] = $reservation->billing_status === 'paid';
            Mail::to($details['recipient'])->send(new FacilityBillingMail($details, $message->kind));
            $message->update(['status' => 'sent', 'sent_at' => now(), 'snapshot' => $details]);
            if ($isSoa) {
                $values = ['soa_email_pending' => false, 'soa_sent_at' => today()];
                if (!$reservation->payment_due_at) { $values['payment_due_at'] = today()->addDays(15); }
                if ($reservation->billing_status !== 'paid') { $values['billing_status'] = 'billed'; }
                $reservation->update($values);
            }
        });
        $message = FacilityBillingEmail::find($this->messageId);
        if ($message && in_array($message->status, ['sent', 'skipped'])) {
            try { Storage::disk($message->snapshot['disk'])->delete($message->snapshot['attachment']); }
            catch (\Throwable $exception) { report($exception); }
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $message = FacilityBillingEmail::find($this->messageId);
        if ($message?->status === 'queued') {
            $message->update(['status' => 'failed']);
            Storage::disk($message->snapshot['disk'])->delete($message->snapshot['attachment']);
        }
    }
}
