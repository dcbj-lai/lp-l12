<?php

namespace App\Services;

use App\Models\FacilityReservationAccessLink;
use App\Models\FacilityReservationCancellation;
use App\Models\ResourceReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FacilityRequesterCancellationService
{
    public function link(ResourceReservation $reservation): ?string
    {
        if (!$reservation->exists || !$reservation->requester_email) return null;
        $expires = $reservation->end_datetime->copy()->addDays(7)->min(now()->addYear());
        if ($expires->isPast()) return null;
        $token = Str::random(64);
        FacilityReservationAccessLink::create(['reservation_id' => $reservation->id,
            'token_hash' => hash('sha256', $token), 'recipient_hash' => $this->recipientHash($reservation), 'expires_at' => $expires]);
        return route('facilities.requester.show', ['token' => $token]);
    }

    private function recipientHash(ResourceReservation $reservation): string
    {
        return hash('sha256', mb_strtolower(trim((string) $reservation->requester_email)));
    }

    public function resolve(string $token): FacilityReservationAccessLink
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/', $token), 404);
        $link = FacilityReservationAccessLink::where('token_hash', hash('sha256', $token))->where('expires_at', '>', now())->firstOrFail();
        $reservation = ResourceReservation::withTrashed()->findOrFail($link->reservation_id);
        abort_unless(hash_equals($link->recipient_hash, $this->recipientHash($reservation)), 404);
        return $link;
    }

    public function bookings(FacilityReservationAccessLink $link)
    {
        return ResourceReservation::withTrashed()->with(['rooms', 'resource', 'equipment', 'latestCancellationRequest'])
            ->whereKey($link->reservation_id)->get();
    }

    public function seriesBookings(FacilityReservationAccessLink $link)
    {
        $anchor = ResourceReservation::withTrashed()->findOrFail($link->reservation_id);
        if (!$anchor->recurrence_series_id) return collect();
        return ResourceReservation::where('recurrence_series_id', $anchor->recurrence_series_id)
            ->withExists('paymentProofs')
            ->where('requester_email', $anchor->requester_email)->where('start_datetime', '>', now())
            ->whereIn('status', ['pending', 'approved'])
            ->whereNotIn('id', FacilityReservationCancellation::select('reservation_id')
                ->where(fn ($q) => $q->where('status', 'review')->orWhere(fn ($q) => $q->where('access_link_id', $link->id)->where('status', 'cancelled'))))
            ->orderBy('start_datetime')->orderBy('id')->get();
    }

    public function request(FacilityReservationAccessLink $link, int $id, string $reason, string $scope = 'date', array $ids = []): array
    {
        abort_unless($id === (int) $link->reservation_id, 404);
        abort_unless(in_array($scope, ['date', 'series', 'selected'], true), 422);
        $anchor = ResourceReservation::withTrashed()->findOrFail($link->reservation_id);
        if ($scope !== 'date') abort_unless($anchor->recurrence_series_id, 422);
        // Limit series cancellation to the dates shown on the submitted confirmation page.
        $bookings = $scope !== 'date' ? $this->seriesBookings($link)->whereIn('id', $ids) : $this->bookings($link);
        $results = [];
        foreach ($bookings as $booking) {
            try {
                $outcome = DB::transaction(function () use ($booking, $link, $reason, $scope, $anchor) {
                    $current = ResourceReservation::withTrashed()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
                    if (!hash_equals($link->recipient_hash, $this->recipientHash($current))) return null;
                    if ($scope !== 'date' && $current->recurrence_series_id !== $anchor->recurrence_series_id) return null;
                    if ($current->trashed() || $current->start_datetime->lte(now()) || !in_array($current->status, ['pending', 'approved'])) return null;
                    // The same email link cannot cancel a date again after an admin restores it.
                    if (FacilityReservationCancellation::where('reservation_id', $current->id)
                        ->where(fn ($q) => $q->where('status', 'review')->orWhere(fn ($q) => $q->where('access_link_id', $link->id)->where('status', 'cancelled')))->exists()) return null;
                    $review = $current->soa_path || $current->paid_at || $current->payment_proof_path
                        || $current->billing_status !== 'unbilled' || $current->paymentProofs()->exists();
                    if (!$review) $this->cancelBooking($current);
                    $request = FacilityReservationCancellation::create(['reservation_id' => $current->id, 'access_link_id' => $link->id,
                        'status' => $review ? 'review' : 'cancelled', 'reason' => $reason ?: null, 'resolved_at' => $review ? null : now()]);
                    return [$current, $request];
                });
                if ($outcome) {
                    $this->notify(...$outcome);
                    $results[] = ['title' => $booking->title, 'date' => $booking->start_datetime->format('M j, Y g:i A'), 'status' => $outcome[1]->status];
                }
            } catch (\Throwable $e) {
                report($e);
                $results[] = ['title' => $booking->title, 'date' => $booking->start_datetime->format('M j, Y g:i A'), 'status' => 'failed'];
            }
        }
        return $results;
    }

    private function cancelBooking(ResourceReservation $reservation): void
    {
        if ($reservation->google_event_id && !app(ResourceReservationService::class)->deleteGoogleCalendarEvent($reservation)) {
            throw new \RuntimeException('Calendar removal failed. The reservation remains unchanged.');
        }
        if ($reservation->isDirty('google_event_id')) $reservation->save();
        $reservation->delete(); // Retain event, attachments, payment evidence and audit history.
    }

    public function review(int $requestId, bool $approve): void
    {
        abort_unless(auth()->user()?->hasRole('facility.admin'), 403);
        $result = DB::transaction(function () use ($requestId, $approve) {
            $initial = FacilityReservationCancellation::findOrFail($requestId);
            $booking = ResourceReservation::withTrashed()->whereKey($initial->reservation_id)->lockForUpdate()->firstOrFail();
            $request = FacilityReservationCancellation::whereKey($requestId)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'review') return null;
            if ($approve) {
                if ($booking->trashed() || $booking->start_datetime->lte(now()) || !in_array($booking->status, ['pending', 'approved'])) {
                    throw new \RuntimeException('This date can no longer be cancelled. Decline the request or contact the organizer.');
                }
                $this->cancelBooking($booking);
            }
            $request->update(['status' => $approve ? 'cancelled' : 'declined', 'resolved_at' => now(), 'resolved_by' => auth()->id()]);
            return [$booking, $request];
        });
        if ($result) $this->notify(...$result);
    }

    private function notify(ResourceReservation $booking, FacilityReservationCancellation $request): void
    {
        $details = ['title' => $booking->title, 'schedule' => $booking->start_datetime->format('M j, Y g:i A').'–'.$booking->end_datetime->format('M j, Y g:i A'),
            'rooms' => $booking->room_names, 'reason' => $request->reason, 'status' => $request->status, 'id' => $booking->id];
        foreach (array_unique(array_filter([$booking->requester_email, config('mail.resource_admin')])) as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            try { Mail::to($email)->queue(new \App\Mail\FacilityReservationCancellationMail($details)); }
            catch (\Throwable $e) { report($e); }
        }
    }
}
