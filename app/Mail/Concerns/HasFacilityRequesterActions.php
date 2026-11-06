<?php

namespace App\Mail\Concerns;

use App\Models\ResourceReservation;
use App\Services\FacilityRequesterEmailActions;

trait HasFacilityRequesterActions
{
    protected function requesterActionData(?int $reservationId): array
    {
        // Requester magic links must never be copied into internal or multi-recipient messages.
        if (!$reservationId || count($this->to) > 1 || $this->cc || $this->bcc) return [];
        $reservation = ResourceReservation::find($reservationId);
        $recipient = $this->to[0]['address'] ?? ($this->details['recipient'] ?? $reservation?->requester_email);
        return app(FacilityRequesterEmailActions::class)->forReservation($reservation, $recipient);
    }
}
