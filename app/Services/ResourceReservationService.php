<?php

namespace App\Services;

use App\Mail\ResourceBookingApproved;
use App\Models\ResourceReservation;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\DB;

class ResourceReservationService
{
    /**
     * Check if a resource (room or equipment) is available
     */
    public function isResourceAvailable(int $resourceId, $start, $end): bool
    {
        return !ResourceReservation::where('resource_id', $resourceId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            })
            ->exists()
            &&
            !DB::table('resource_reservation_items')
                ->join('resource_reservations', 'resource_reservation_items.reservation_id', '=', 'resource_reservations.id')
                ->where('resource_reservation_items.resource_id', $resourceId)
                ->whereIn('resource_reservations.status', ['pending', 'approved'])
                ->where(function ($query) use ($start, $end) {
                    $query->where('resource_reservations.start_datetime', '<', $end)
                        ->where('resource_reservations.end_datetime', '>', $start);
                })
                ->exists();
    }

    /**
     * Validate all resources (room + equipment)
     */
    public function validateAvailability(?int $primaryResourceId, array $equipmentIds, $start, $end): void
    {
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
            if (!$this->isResourceAvailable($id, $start, $end)) {
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

    public function approve(ResourceReservation $reservation, int $approverId): ResourceReservation
    {
        if ($reservation->status !== 'pending') {
            throw new \Exception('Only pending reservations can be approved.');
        }

        // 🔥 Create Google Calendar event FIRST
        $googleEventId = app(GoogleCalendarService::class)
            ->createEvent($reservation);

        // ✅ Update DB
        $reservation->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'google_event_id' => $googleEventId,
        ]);

        $reservation->load('resource', 'equipment');

        // 🔥 Notify requester
        if ($reservation->requester_email) {
            \Mail::to($reservation->requester_email)
                ->queue(new ResourceBookingApproved($reservation));
        }

        return $reservation;
    }
}
