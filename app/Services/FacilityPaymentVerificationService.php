<?php

namespace App\Services;

use App\Mail\FacilityPaymentReceived;
use App\Mail\FacilityPaymentReviewMail;
use App\Models\{FacilityPaymentLink, FacilityPaymentSubmission, ResourceReservation, User};
use App\Support\FacilityBillingStorage;
use Illuminate\Support\Facades\{DB, Mail};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FacilityPaymentVerificationService
{
    public const ROLE = 'facility.payment-verifier';

    public function link(ResourceReservation $reservation): ?string
    {
        if (!$this->payable($reservation) || !filter_var($reservation->requester_email, FILTER_VALIDATE_EMAIL)) return null;
        $token = Str::random(64);
        FacilityPaymentLink::create(['reservation_id' => $reservation->id, 'token_hash' => hash('sha256', $token),
            'recipient_hash' => $this->recipientHash($reservation), 'soa_hash' => hash('sha256', $reservation->soa_path),
            'expires_at' => now()->addDays(90)]);
        return route('facilities.payment.show', $token);
    }

    private function recipientHash(ResourceReservation $reservation): string
    {
        return hash('sha256', mb_strtolower(trim((string) $reservation->requester_email)));
    }

    public function resolve(string $token): array
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/', $token), 404);
        $link = FacilityPaymentLink::where('token_hash', hash('sha256', $token))->where('expires_at', '>', now())->firstOrFail();
        $reservation = ResourceReservation::findOrFail($link->reservation_id);
        abort_unless($reservation->status === 'approved' && $reservation->soa_path
            && hash_equals($link->recipient_hash, $this->recipientHash($reservation))
            && hash_equals($link->soa_hash, hash('sha256', $reservation->soa_path)), 404);
        return [$link, $reservation];
    }

    public function payable(ResourceReservation $reservation): bool
    {
        return !$reservation->trashed() && $reservation->status === 'approved' && (bool) $reservation->soa_path
            && !$reservation->soa_locked && $reservation->payment_required
            && $reservation->latestCancellationRequest?->status !== 'review';
    }

    public function canReview(User $user, ResourceReservation $reservation): bool
    {
        return $user->is_active && $user->hasRole(self::ROLE);
    }

    public function canView(User $user, ResourceReservation $reservation): bool
    {
        return ($user->hasRole('facility.admin') && !$user->hasRole('facility.viewer')) || $this->canReview($user, $reservation);
    }

    public function submit(string $token, array $data): FacilityPaymentSubmission
    {
        $paths = [];
        try {
            $submission = DB::transaction(function () use ($token, $data, &$paths) {
                [$link, $resolved] = $this->resolve($token);
                $reservation = ResourceReservation::whereKey($resolved->id)->lockForUpdate()->firstOrFail();
                // Resolve again after acquiring the reservation lock: SOA/recipient may have changed.
                $this->resolve($token);
                if (!$this->payable($reservation)) throw ValidationException::withMessages(['proofs' => 'This reservation is not accepting payment submissions.']);
                if ($reservation->paymentSubmissions()->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages(['proofs' => 'Payment verification is already pending. Please wait for finance to review it.']);
                }
                foreach ($data['proofs'] as $upload) {
                    $path = $upload->storeAs('facility-billing/payment-submissions', Str::uuid().'.'.$upload->getClientOriginalExtension(), FacilityBillingStorage::uploadDisk());
                    if (!$path) throw new \RuntimeException('Proof could not be uploaded. Please try again.');
                    $paths[] = ['path' => $path, 'original_name' => $upload->getClientOriginalName()];
                }
                return $reservation->paymentSubmissions()->create(['access_link_id' => $link->id, 'soa_path' => $reservation->soa_path,
                    'paid_on' => $data['paid_on'], 'reference' => $data['reference'], 'files' => $paths]);
            });
        } catch (\Throwable $e) {
            foreach ($paths as $file) FacilityBillingStorage::disk($file['path'])->delete($file['path']);
            throw $e;
        }
        $this->notifyFinance($submission);
        return $submission;
    }

    public function notifyFinance(FacilityPaymentSubmission $submission): void
    {
        $reservation = $submission->reservation;
        $recipients = User::role(self::ROLE)->where('is_active', true)->get();
        foreach ($recipients as $user) {
            $this->queueMail($user->email, new FacilityPaymentReviewMail('Payment verification: '.$reservation->title,
                'A requester submitted proof of full payment for '.$reservation->title.'. Review the SOA and proof before confirming payment.',
                route('facilities.payments.review', $reservation->id), 'Review payment'));
        }
    }

    public function review(int $submissionId, User $reviewer, string $decision, ?string $note): void
    {
        abort_unless(in_array($decision, ['confirmed', 'correction'], true), 422);
        $mail = DB::transaction(function () use ($submissionId, $reviewer, $decision, $note) {
            $initial = FacilityPaymentSubmission::findOrFail($submissionId);
            $reservation = ResourceReservation::whereKey($initial->reservation_id)->lockForUpdate()->firstOrFail();
            $submission = FacilityPaymentSubmission::whereKey($submissionId)->lockForUpdate()->firstOrFail();
            abort_unless($this->canReview($reviewer, $reservation), 403);
            if ($submission->status !== 'pending') throw ValidationException::withMessages(['decision' => 'This submission has already been reviewed.']);
            if (!$this->payable($reservation) || $reservation->soa_path !== $submission->soa_path) {
                throw ValidationException::withMessages(['decision' => 'The reservation or SOA has changed. This proof cannot mark the current SOA paid. Request a new submission.']);
            }
            $submission->update(['status' => $decision, 'review_note' => $note, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);
            if ($decision === 'confirmed') {
                foreach ($submission->files as $file) {
                    if (!FacilityBillingStorage::disk($file['path'])->exists($file['path'])) {
                        throw ValidationException::withMessages(['decision' => 'A proof file is missing. Payment was not confirmed.']);
                    }
                    $reservation->paymentProofs()->create($file + ['paid_on' => $submission->paid_on->toDateString(), 'recorded_by' => $reviewer->id]);
                }
                $reservation->update(['billing_status' => 'paid', 'paid_at' => $submission->paid_on->startOfDay(),
                    'payment_proof_path' => $reservation->payment_proof_path ?: $submission->files[0]['path'], 'soa_email_pending' => false]);
                return [$reservation->requester_email, new FacilityPaymentReceived(['title' => $reservation->title, 'reservation_id' => $reservation->id,
                    'event_date' => $reservation->start_datetime->format('M j, Y g:i A').'–'.$reservation->end_datetime->format('M j, Y g:i A'),
                    'room' => $reservation->room_names, 'paid_date' => $reservation->paid_at->format('M j, Y')])];
            }
            return [$reservation->requester_email, new FacilityPaymentReviewMail('Payment proof needs correction: '.$reservation->title,
                'Finance requested a correction for '.$reservation->title.":\n".$note,
                '', '', $reservation->id)];
        });
        $this->queueMail(...$mail);
    }

    private function queueMail(string $recipient, $mail): void
    {
        try { Mail::to($recipient)->queue($mail); }
        catch (\Throwable $e) { report($e); } // A mail outage must not undo a saved proof or verified payment.
    }
}
