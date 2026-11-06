<?php

namespace App\Services;

use App\Models\ResourceReservation;

class FacilityRequesterEmailActions
{
    public function forReservation(?ResourceReservation $reservation, ?string $recipient): array
    {
        if (!$reservation || $reservation->trashed() || !in_array($reservation->status, ['pending', 'approved'], true)
            || !$recipient || !hash_equals(mb_strtolower(trim((string) $reservation->requester_email)), mb_strtolower(trim($recipient)))) return [];

        $review = $reservation->latestCancellationRequest?->status === 'review';
        $manageUrl = app(FacilityRequesterCancellationService::class)->link($reservation);
        $pending = !$reservation->soa_locked && $reservation->paymentSubmissions()->where('status', 'pending')->exists();
        $paymentUrl = !$pending ? app(FacilityPaymentVerificationService::class)->link($reservation) : null;

        return ['manage_url' => $manageUrl,
            'manage_label' => $reservation->start_datetime->isFuture() && !$review ? 'View / Cancel reservation' : 'View reservation',
            'payment_url' => $paymentUrl, 'payment_pending' => $pending && !$review];
    }
}
