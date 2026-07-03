<?php

namespace App\Services;

use App\Mail\ResourceBookingApproved;
use App\Mail\ResourceBookingRejected;
use App\Models\ResourceReservation;
use App\Services\GoogleCalendarService;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ResourceReservationService
{
    /**
     * Check if a resource (room or equipment) is available
     */
    public function isResourceAvailable(int $resourceId, $start, $end, ?int $ignoreReservationId = null): bool
    {
        $primaryConflict = ResourceReservation::where('resource_id', $resourceId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($ignoreReservationId, fn ($query) => $query->whereKeyNot($ignoreReservationId))
            ->where(function ($query) use ($start, $end) {
                $query->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            });

        $itemConflict = DB::table('resource_reservation_items')
            ->join('resource_reservations', 'resource_reservation_items.reservation_id', '=', 'resource_reservations.id')
            ->where('resource_reservation_items.resource_id', $resourceId)
            ->whereIn('resource_reservations.status', ['pending', 'approved'])
            ->when($ignoreReservationId, fn ($query) => $query->where('resource_reservations.id', '!=', $ignoreReservationId))
            ->where(function ($query) use ($start, $end) {
                $query->where('resource_reservations.start_datetime', '<', $end)
                    ->where('resource_reservations.end_datetime', '>', $start);
            });

        return !$primaryConflict->exists() && !$itemConflict->exists();
    }

    /**
     * Validate all resources (room + equipment)
     */
    public function validateAvailability(
        ?int $primaryResourceId,
        array $equipmentIds,
        $start,
        $end,
        ?int $ignoreReservationId = null
    ): void {
        $ids = array_filter(array_merge(
            $primaryResourceId ? [$primaryResourceId] : [],
            $equipmentIds
        ));

        if (empty($ids)) {
            return;
        }

        // 🔥 Load all resources in one query
        $resources = \App\Models\Resource::whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $conflicts = [];

        foreach ($ids as $id) {
            if (!$this->isResourceAvailable($id, $start, $end, $ignoreReservationId)) {
                if (isset($resources[$id])) {
                    $conflicts[] = $resources[$id]->name;
                }
            }
        }

        if (!empty($conflicts)) {
            throw new \Exception(
                'The following resources are not available for the selected time: ' .
                collect($conflicts)->join(', ')
            );
        }
    }

    /**
     * Create reservation safely
     */
    public function create(array $data): ResourceReservation
    {
        $reservation = DB::transaction(function () use ($data) {

            $this->validateAvailability(
                $data['resource_id'] ?? null,
                $data['equipment_ids'] ?? [],
                $data['start_datetime'],
                $data['end_datetime']
            );

            $reservation = ResourceReservation::create([
                'user_id' => $data['user_id'] ?? null,
                'requester_email' => $data['requester_email'] ?? null,
                'resource_id' => $data['resource_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_datetime' => $data['start_datetime'],
                'end_datetime' => $data['end_datetime'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'attachment_path' => $data['attachment_path'] ?? null,
            ]);

            if (!empty($data['equipment_ids'])) {
                $reservation->equipment()->sync($data['equipment_ids']);
            }

            return $reservation;
        });

        // 🔥 IMPORTANT: load relations AFTER transaction
        $reservation->load('resource', 'equipment');

        // 🔥 Send admin email (DO NOT break flow if it fails)
        \Mail::to(config('mail.resource_admin'))
            ->queue(new \App\Mail\ResourceBookingAdminNotification($reservation));
        if (!empty($reservation->requester_email)) {
            \Mail::to($reservation->requester_email)
                ->queue(new \App\Mail\ResourceBookingRequesterConfirmation($reservation));
        }

        return $reservation;
    }

    public function update(ResourceReservation $reservation, array $data): ResourceReservation
    {
        $payload = array_merge([
            'user_id' => $reservation->user_id,
            'requester_email' => $reservation->requester_email,
            'resource_id' => $reservation->resource_id,
            'equipment_ids' => $reservation->equipment()->pluck('resources.id')->all(),
            'title' => $reservation->title,
            'description' => $reservation->description,
            'start_datetime' => $reservation->start_datetime,
            'end_datetime' => $reservation->end_datetime,
            'notes' => $reservation->notes,
            'attachment_path' => $reservation->attachment_path,
        ], $data);

        $wasApproved = $reservation->status === 'approved';

        if ($wasApproved && $reservation->google_event_id && !$this->deleteGoogleCalendarEvent($reservation)) {
            throw new \Exception('Unable to delete the existing Google Calendar event. Reservation was not updated.');
        }

        $reservation = DB::transaction(function () use ($reservation, $payload) {
            $this->validateAvailability(
                $payload['resource_id'] ?? null,
                $payload['equipment_ids'] ?? [],
                $payload['start_datetime'],
                $payload['end_datetime'],
                $reservation->id
            );

            $reservation->update([
                'user_id' => $payload['user_id'] ?? null,
                'requester_email' => $payload['requester_email'] ?? null,
                'resource_id' => $payload['resource_id'] ?? null,
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'start_datetime' => $payload['start_datetime'],
                'end_datetime' => $payload['end_datetime'],
                'notes' => $payload['notes'] ?? null,
                'attachment_path' => $payload['attachment_path'] ?? null,
            ]);

            $reservation->equipment()->sync($payload['equipment_ids'] ?? []);

            return $reservation->fresh(['resource', 'equipment']);
        });

        if ($wasApproved) {
            $this->recreateGoogleCalendarEvent($reservation);
        }

        return $reservation->fresh(['resource', 'equipment']);
    }

    public function approveReservation(
        ResourceReservation $reservation,
        int $approverId,
        ?string $note = null
    ): ResourceReservation {

        if ($reservation->status === 'approved') {
            throw new \Exception('Reservation is already approved.');
        }

        // Google Calendar (safe)
        $googleEventId = null;

        try {
            $googleEventId = app(GoogleCalendarService::class)
                ->createEvent($reservation);
        } catch (\Throwable $e) {
            \Log::error('Google Calendar event creation failed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }

        $reservation->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_note' => $note,
            'google_event_id' => $googleEventId,
        ]);

        $reservation->load(['resource', 'equipment']);

        if ($reservation->requester_email) {
            try {
                \Mail::to($reservation->requester_email)
                    ->queue(new ResourceBookingApproved($reservation));
            } catch (\Throwable $e) {
                \Log::error('Failed to send approval email', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $reservation;
    }

    public function rejectReservation(
        ResourceReservation $reservation,
        int $approverId,
        ?string $note = null
    ): ResourceReservation {

        if ($reservation->status === 'rejected') {
            throw new \Exception('Reservation is already rejected.');
        }

        if (!$note) {
            throw new \Exception('Rejection reason is required.');
        }

        if ($reservation->google_event_id && !$this->deleteGoogleCalendarEvent($reservation)) {
            throw new \Exception('Unable to delete the Google Calendar event. Reservation was not rejected.');
        }

        $reservation->update([
            'status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_note' => $note, // ✅ same column
            'google_event_id' => $reservation->google_event_id,
        ]);

        $reservation->load(['resource', 'equipment']);

        if ($reservation->requester_email) {
            \Mail::to($reservation->requester_email)
                ->send(new ResourceBookingRejected($reservation));
        }

        return $reservation;
    }

    public function deleteGoogleCalendarEvent(ResourceReservation $reservation): bool
    {
        if (!$reservation->google_event_id) {
            return false;
        }

        $eventId = $reservation->google_event_id;

        try {
            app(GoogleCalendarService::class)->deleteEvent($eventId);
            $reservation->google_event_id = null;

            return true;
        } catch (GoogleServiceException $e) {
            if ((int) $e->getCode() === 404) {
                Log::info('Google Calendar event was already missing for resource reservation', [
                    'reservation_id' => $reservation->id,
                    'event_id' => $eventId,
                ]);

                $reservation->google_event_id = null;

                return true;
            }

            Log::warning('Google Calendar event deletion failed for resource reservation', [
                'reservation_id' => $reservation->id,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('Google Calendar event deletion failed for resource reservation', [
                'reservation_id' => $reservation->id,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function recreateGoogleCalendarEvent(ResourceReservation $reservation): void
    {
        if ($reservation->google_event_id && !$this->deleteGoogleCalendarEvent($reservation)) {
            return;
        }

        $googleEventId = null;

        try {
            $googleEventId = app(GoogleCalendarService::class)
                ->createEvent($reservation);
        } catch (\Throwable $e) {
            Log::error('Google Calendar event recreation failed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }

        $reservation->update([
            'google_event_id' => $googleEventId,
        ]);
    }
}
