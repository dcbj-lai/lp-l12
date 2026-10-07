<?php

namespace App\Services;

use App\Jobs\SendFacilityBillingEmail;
use App\Models\FacilityBillingEmail;
use App\Models\ResourceReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FacilityBillingEmailService
{
    public function queue(int $id, string $action): void
    {
        abort_unless(auth()->user()?->hasRole('facility.admin'), 403);
        abort_unless(in_array($action, ['soa', 'reminder'], true), 422);
        $this->queueMessage($id, $action, auth()->id());
    }

    public function queueScheduledReminder(int $id): void
    {
        $this->queueMessage($id, 'reminder', null, true);
    }

    private function queueMessage(int $id, string $action, ?int $requestedBy, bool $scheduled = false): void
    {
        $copy = null;
        $message = null;
        $disk = config('filesystems.facility_upload_disk');
        try {
            $message = DB::transaction(function () use ($id, $action, $disk, $requestedBy, $scheduled, &$copy) {
                $reservation = ResourceReservation::whereKey($id)->lockForUpdate()->firstOrFail();
                if ($reservation->soa_locked) {
                    throw new \RuntimeException('Billing emails cannot be sent after payment is recorded.');
                }
                if ($reservation->status !== 'approved' || !$reservation->soa_path) {
                    throw new \RuntimeException('An approved reservation with an SOA is required.');
                }
                if (!filter_var($reservation->requester_email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('A valid requester email address is required.');
                }
                if (FacilityBillingEmail::where('reservation_id', $id)->where('status', 'queued')->exists()) {
                    throw new \RuntimeException('A billing email is already queued for this reservation.');
                }
                if ($action === 'reminder') {
                    if ($reservation->billing_status !== 'billed' || !$reservation->payment_due_at || $reservation->soa_email_pending) {
                        throw new \RuntimeException('Reminders require an unpaid, billed reservation with the current SOA sent.');
                    }
                    if ($scheduled) {
                        if (!$reservation->payment_due_at->isSameDay(today()->addDays(7))) {
                            throw new \RuntimeException('The seven-day reminder is not due today.');
                        }
                        $alreadySent = FacilityBillingEmail::where('reservation_id', $id)->where('kind', 'upcoming')->where('status', 'sent')
                            ->get()->contains(fn ($email) => ($email->snapshot['soa_path'] ?? null) === $reservation->soa_path);
                        if ($alreadySent) { throw new \RuntimeException('The current SOA has already received an upcoming reminder.'); }
                    }
                    if (FacilityBillingEmail::where('reservation_id', $id)->whereIn('kind', ['upcoming', 'due_today', 'overdue'])->where('sent_at', '>=', today())->exists()) {
                        throw new \RuntimeException('A payment reminder has already been sent today.');
                    }
                    $kind = $reservation->payment_due_at->isBefore(today()) ? 'overdue' : ($reservation->payment_due_at->isSameDay(today()) ? 'due_today' : 'upcoming');
                } else {
                    if (!$reservation->soa_email_pending) {
                        throw new \RuntimeException('This SOA has already been sent. Upload a replacement to send an updated SOA.');
                    }
                    $kind = $reservation->soa_sent_at ? 'updated_soa' : 'soa';
                }
                $copy = 'reservations/billing-mail/' . Str::uuid() . '.' . pathinfo($reservation->soa_path, PATHINFO_EXTENSION);
                if (!Storage::disk($disk)->copy($reservation->soa_path, $copy)) {
                    throw new \RuntimeException('The SOA could not be attached. Please try again.');
                }
                $message = FacilityBillingEmail::create([
                    'reservation_id' => $id, 'requested_by' => $requestedBy, 'kind' => $kind,
                    'snapshot' => [
                        'reservation_id' => $id, 'title' => $reservation->title,
                        'recipient' => $reservation->requester_email,
                        'event_date' => $reservation->start_datetime->format('M j, Y g:i A') . '–' . $reservation->end_datetime->format('M j, Y g:i A'),
                        'room' => $reservation->room_names,
                        'due_date' => $reservation->payment_due_at?->format('M j, Y'),
                        'paid' => $reservation->billing_status === 'paid',
                        'disk' => $disk, 'attachment' => $copy, 'soa_path' => $reservation->soa_path,
                    ],
                ]);
                return $message;
            });
            SendFacilityBillingEmail::dispatch($message->id);
        } catch (\Throwable $e) {
            if ($message?->fresh()?->status === 'queued') { $message->update(['status' => 'failed']); }
            if ($copy) { Storage::disk($disk)->delete($copy); }
            throw $e;
        }
    }
}
