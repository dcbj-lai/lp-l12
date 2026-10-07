<?php

namespace App\Services;

use App\Mail\ResourceBookingApproved;
use App\Mail\ResourceBookingRejected;
use App\Models\ResourceReservation;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use App\Services\GoogleCalendarService;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;

class ResourceReservationService
{
    /**
     * Check if a resource (room or equipment) is available
     */
    public function isResourceAvailable(int $resourceId, $start, $end, ?int $ignoreReservationId = null): bool
    {
        $start = CarbonImmutable::parse($start)->toDateTimeString();
        $end = CarbonImmutable::parse($end)->toDateTimeString();
        $resource = Resource::findOrFail($resourceId);
        if ($resource->isEquipment()) {
            return $this->availableEquipmentQuantity($resourceId, $start, $end, $ignoreReservationId) > 0;
        }
        if (!$resource->isRoom() || (int) $resource->capacity < 1) {
            return false;
        }

        $primaryConflict = ResourceReservation::where(fn ($query) => $query->where('resource_id', $resourceId)
            ->orWhereHas('rooms', fn ($rooms) => $rooms->whereKey($resourceId)))
            ->where('status', 'approved')
            ->when($ignoreReservationId, fn ($query) => $query->whereKeyNot($ignoreReservationId))
            ->where(function ($query) use ($start, $end) {
                $query->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            });

        return !$primaryConflict->exists();
    }

    public function availableEquipmentQuantity(int $resourceId, $start, $end, ?int $ignoreReservationId = null): int
    {
        $start = CarbonImmutable::parse($start)->toDateTimeString();
        $end = CarbonImmutable::parse($end)->toDateTimeString();
        $total = Resource::findOrFail($resourceId)->total_quantity;
        if ($total < 1) {
            return 0;
        }
        $bookings = DB::table('resource_reservation_items')
            ->join('resource_reservations', 'resource_reservation_items.reservation_id', '=', 'resource_reservations.id')
            ->where('resource_reservation_items.resource_id', $resourceId)
            ->whereNull('resource_reservations.deleted_at')
            ->where('resource_reservations.status', 'approved')
            ->when($ignoreReservationId, fn ($query) => $query->where('resource_reservations.id', '!=', $ignoreReservationId))
            ->where('resource_reservations.start_datetime', '<', $end)
            ->where('resource_reservations.end_datetime', '>', $start)
            ->get(['resource_reservations.start_datetime', 'resource_reservations.end_datetime', 'resource_reservation_items.quantity']);

        $events = [];
        foreach ($bookings as $booking) {
            $events[] = [max($start, $booking->start_datetime), (int) $booking->quantity];
            $events[] = [min($end, $booking->end_datetime), -(int) $booking->quantity];
        }
        usort($events, fn ($a, $b) => strcmp($a[0], $b[0]) ?: ($a[1] <=> $b[1]));
        $used = $peak = 0;
        foreach ($events as [, $delta]) {
            $used += $delta;
            $peak = max($peak, $used);
        }
        return max(0, $total - $peak);
    }

    public function maximumEquipmentUsage(int $resourceId): int
    {
        $items = DB::table('resource_reservation_items')
            ->join('resource_reservations', 'resource_reservation_items.reservation_id', '=', 'resource_reservations.id')
            ->where('resource_reservation_items.resource_id', $resourceId)
            ->whereNull('resource_reservations.deleted_at')
            ->where('resource_reservations.status', 'approved')
            ->get(['resource_reservations.start_datetime', 'resource_reservations.end_datetime', 'resource_reservation_items.quantity']);

        $events = [];
        foreach ($items as $item) {
            $events[] = [$item->start_datetime, (int) $item->quantity];
            $events[] = [$item->end_datetime, -(int) $item->quantity];
        }
        usort($events, fn ($a, $b) => strcmp($a[0], $b[0]) ?: ($a[1] <=> $b[1]));
        $used = $peak = 0;
        foreach ($events as [, $delta]) {
            $used += $delta;
            $peak = max($peak, $used);
        }
        return $peak;
    }

    /**
     * Validate all resources (room + equipment)
     */
    public function validateAvailability(
        int|array|null $primaryResourceId,
        array $equipmentQuantities,
        $start,
        $end,
        ?int $ignoreReservationId = null
    ): void {
        if (!(array) $primaryResourceId) {
            throw new \InvalidArgumentException('Room selection is required.');
        }
        $this->validateRequestResources($primaryResourceId, $equipmentQuantities);
        $conflicts = $this->approvedConflictNames($primaryResourceId, $equipmentQuantities, $start, $end, $ignoreReservationId);
        if ($conflicts) {
            throw new \Exception('The following resources are not available for the selected time: ' . collect($conflicts)->join(', '));
        }
    }

    public function validateRequestResources(int|array|null $primaryResourceId, array $equipmentQuantities): void
    {
        $roomIds = (array) $primaryResourceId;
        foreach ($roomIds as $roomId) {
            $room = Resource::findOrFail($roomId);
            if (!$room->isRoom()) {
                throw new \InvalidArgumentException('Select a room for the reservation.');
            }
            if ((int) $room->capacity < 1) {
                throw new \InvalidArgumentException($room->name . ' has no capacity.');
            }
        }
        foreach ($equipmentQuantities as $id => $quantity) {
            $resource = Resource::findOrFail($id);
            if (!$resource->isEquipment() || $quantity < 1 || $quantity > $resource->total_quantity) {
                throw new \InvalidArgumentException($resource->name . ' has insufficient total quantity.');
            }
        }
    }

    public function approvedConflictNames(int|array|null $primaryResourceId, array $equipmentQuantities, $start, $end, ?int $ignoreReservationId = null): array
    {
        $conflicts = [];
        foreach ((array) $primaryResourceId as $roomId) {
            if (!$this->isResourceAvailable((int) $roomId, $start, $end, $ignoreReservationId)) {
                $conflicts[] = Resource::findOrFail($roomId)->name;
            }
        }
        foreach ($equipmentQuantities as $id => $quantity) {
            $resource = Resource::findOrFail($id);
            if ($quantity > $this->availableEquipmentQuantity($id, $start, $end, $ignoreReservationId)) {
                $conflicts[] = $resource->name;
            }
        }
        return $conflicts;
    }

    public function approvedConflictsForReservation(ResourceReservation $reservation): array
    {
        if ($reservation->trashed() || $reservation->status !== 'pending') {
            return [];
        }
        $reservation->loadMissing('equipment');
        return $this->approvedConflictNames(
            $reservation->roomIds(),
            $reservation->equipment->mapWithKeys(fn ($item) => [$item->id => $item->pivot->quantity])->all(),
            $reservation->start_datetime,
            $reservation->end_datetime,
            $reservation->id
        );
    }

    /** Approved bookings that currently consume a resource blocking this request. */
    public function blockingApprovedReservations(ResourceReservation $reservation): Collection
    {
        if ($reservation->trashed() || $reservation->status !== 'pending') {
            return collect();
        }

        $reservation->loadMissing('equipment');
        $equipmentIds = $reservation->equipment
            ->filter(fn ($item) => $item->pivot->quantity > $this->availableEquipmentQuantity(
                $item->id, $reservation->start_datetime, $reservation->end_datetime, $reservation->id
            ))->pluck('id')->all();
        $roomConflicts = array_values(array_filter($reservation->roomIds(), fn ($id) => !$this->isResourceAvailable(
            $id, $reservation->start_datetime, $reservation->end_datetime, $reservation->id
        )));

        if (!$roomConflicts && !$equipmentIds) {
            return collect();
        }

        return ResourceReservation::with(['resource', 'rooms', 'equipment'])
            ->where('status', 'approved')
            ->whereKeyNot($reservation->id)
            ->where('start_datetime', '<', $reservation->end_datetime)
            ->where('end_datetime', '>', $reservation->start_datetime)
            ->where(function ($query) use ($roomConflicts, $reservation, $equipmentIds) {
                if ($roomConflicts) {
                    $query->where(fn ($rooms) => $rooms->whereIn('resource_id', $roomConflicts)
                        ->orWhereHas('rooms', fn ($items) => $items->whereIn('resources.id', $roomConflicts)));
                }
                if ($equipmentIds) {
                    $method = $roomConflicts ? 'orWhereHas' : 'whereHas';
                    $query->{$method}('equipment', fn ($items) => $items->whereIn('resources.id', $equipmentIds));
                }
            })
            ->orderBy('start_datetime')->get();
    }

    /**
     * Create reservation safely
     */
    public function create(array $data): ResourceReservation
    {
        $data = $this->normalizeRooms($data);
        $reservation = DB::transaction(function () use ($data) {
            $equipment = $this->equipmentQuantities($data);
            $this->lockResources($data['room_ids'], $equipment);
            $this->validateRequestResources($data['room_ids'], $equipment);

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
                'number_of_pax' => $data['number_of_pax'] ?? null,
                'setup_arrangement' => $data['setup_arrangement'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'consumables' => $data['consumables'] ?? null,
                'floor_plan_path' => $data['floor_plan_path'] ?? null,
                'gate_pass_path' => $data['gate_pass_path'] ?? null,
                'recurrence_series_id' => $data['recurrence_series_id'] ?? null,
            ]);

            $reservation->rooms()->sync($data['room_ids']);
            $reservation->equipment()->sync($this->equipmentPivot($equipment));

            return $reservation;
        });

        // 🔥 IMPORTANT: load relations AFTER transaction
        $reservation->load('resource', 'rooms', 'equipment');

        // 🔥 Send admin email (DO NOT break flow if it fails)
        \Mail::to(config('mail.resource_admin'))
            ->queue(new \App\Mail\ResourceBookingAdminNotification($reservation));
        if (!empty($reservation->requester_email)) {
            \Mail::to($reservation->requester_email)
                ->queue(new \App\Mail\ResourceBookingRequesterConfirmation($reservation));
        }

        return $reservation;
    }

    public function createSeries(array $data, string $frequency, int $occurrences): array
    {
        $data = $this->normalizeRooms($data);
        if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true) || $occurrences < 2 || $occurrences > 52) {
            throw new \InvalidArgumentException('Choose 2 to 52 daily, weekly, or monthly occurrences.');
        }

        $start = CarbonImmutable::parse($data['start_datetime']);
        $end = CarbonImmutable::parse($data['end_datetime']);
        $seriesId = (string) Str::uuid();

        $reservations = DB::transaction(function () use ($data, $frequency, $occurrences, $start, $end, $seriesId) {
            $equipment = $this->equipmentQuantities($data);
            $this->lockResources($data['room_ids'], $equipment);
            $dates = [];
            for ($i = 0; $i < $occurrences; $i++) {
                $offsetStart = match ($frequency) {
                    'daily' => $start->addDays($i),
                    'weekly' => $start->addWeeks($i),
                    'monthly' => $start->addMonthsNoOverflow($i),
                };
                $offsetEnd = $offsetStart->addSeconds($start->diffInSeconds($end));
                foreach ($dates as [$previousStart, $previousEnd]) {
                    if ($offsetStart < $previousEnd && $offsetEnd > $previousStart) {
                        throw new \RuntimeException($offsetStart->format('M j, Y') . ': recurring dates overlap each other.');
                    }
                }
                try {
                    $this->validateRequestResources($data['room_ids'], $equipment);
                } catch (\Throwable $e) {
                    throw new \RuntimeException($offsetStart->format('M j, Y') . ': ' . $e->getMessage(), previous: $e);
                }
                $dates[] = [$offsetStart, $offsetEnd];
            }

            return array_map(function ($dates) use ($data, $equipment, $seriesId) {
                [$occurrenceStart, $occurrenceEnd] = $dates;
                $reservation = ResourceReservation::create([
                    'user_id' => $data['user_id'] ?? null,
                    'requester_email' => $data['requester_email'] ?? null,
                    'resource_id' => $data['resource_id'] ?? null,
                    'title' => $data['title'],
                    'start_datetime' => $occurrenceStart,
                    'end_datetime' => $occurrenceEnd,
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                    'attachment_path' => $data['attachment_path'] ?? null,
                    'number_of_pax' => $data['number_of_pax'] ?? null,
                    'setup_arrangement' => $data['setup_arrangement'] ?? null,
                    'contact_number' => $data['contact_number'] ?? null,
                    'consumables' => $data['consumables'] ?? null,
                    'floor_plan_path' => $data['floor_plan_path'] ?? null,
                    'gate_pass_path' => $data['gate_pass_path'] ?? null,
                    'recurrence_series_id' => $seriesId,
                ]);
                $reservation->rooms()->sync($data['room_ids']);
                $reservation->equipment()->sync($this->equipmentPivot($equipment));
                return $reservation->load('resource', 'rooms', 'equipment');
            }, $dates);
        });

        foreach ($reservations as $reservation) {
            Mail::to(config('mail.resource_admin'))->queue(new \App\Mail\ResourceBookingAdminNotification($reservation));
            if ($reservation->requester_email) {
                Mail::to($reservation->requester_email)->queue(new \App\Mail\ResourceBookingRequesterConfirmation($reservation));
            }
        }

        return $reservations;
    }

    public function createSeriesForDates(array $data, array $eventDates, string $recurrenceLabel): array
    {
        $data = $this->normalizeRooms($data);
        if (count($eventDates) < 2 || count($eventDates) > 52) {
            throw new \InvalidArgumentException('Choose 2 to 52 event dates.');
        }

        $start = CarbonImmutable::parse($data['start_datetime']);
        $end = CarbonImmutable::parse($data['end_datetime']);
        $duration = $start->diffInSeconds($end);
        $seriesId = (string) Str::uuid();

        $reservations = DB::transaction(function () use ($data, $eventDates, $recurrenceLabel, $start, $duration, $seriesId) {
            $equipment = $this->equipmentQuantities($data);
            $this->lockResources($data['room_ids'], $equipment);
            $dates = [];
            foreach ($eventDates as $eventDate) {
                $occurrenceStart = CarbonImmutable::parse($eventDate . ' ' . $start->format('H:i:s'));
                $occurrenceEnd = $occurrenceStart->addSeconds($duration);
                try {
                    $this->validateRequestResources($data['room_ids'], $equipment);
                } catch (\Throwable $e) {
                    throw new \RuntimeException($occurrenceStart->format('M j, Y') . ': ' . $e->getMessage(), previous: $e);
                }
                $dates[] = [$occurrenceStart, $occurrenceEnd];
            }

            return array_map(function ($dates, $index) use ($data, $equipment, $seriesId, $recurrenceLabel, $eventDates) {
                [$occurrenceStart, $occurrenceEnd] = $dates;
                $reservation = ResourceReservation::create([
                    'user_id' => $data['user_id'] ?? null,
                    'requester_email' => $data['requester_email'] ?? null,
                    'resource_id' => $data['resource_id'] ?? null,
                    'title' => $data['title'],
                    'start_datetime' => $occurrenceStart,
                    'end_datetime' => $occurrenceEnd,
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                    'number_of_pax' => $data['number_of_pax'] ?? null,
                    'setup_arrangement' => $data['setup_arrangement'] ?? null,
                    'contact_number' => $data['contact_number'] ?? null,
                    'consumables' => $data['consumables'] ?? null,
                    'floor_plan_path' => $data['floor_plan_path'] ?? null,
                    'gate_pass_path' => $data['gate_pass_path'] ?? null,
                    'recurrence_series_id' => $seriesId,
                    'recurrence_label' => $recurrenceLabel,
                    'recurrence_position' => $index + 1,
                    'recurrence_total' => count($eventDates),
                ]);
                $reservation->rooms()->sync($data['room_ids']);
                $reservation->equipment()->sync($this->equipmentPivot($equipment));
                return $reservation->load('resource', 'rooms', 'equipment');
            }, $dates, array_keys($dates));
        });

        foreach ($reservations as $reservation) {
            Mail::to(config('mail.resource_admin'))->queue(new \App\Mail\ResourceBookingAdminNotification($reservation));
            if ($reservation->requester_email) {
                Mail::to($reservation->requester_email)->queue(new \App\Mail\ResourceBookingRequesterConfirmation($reservation));
            }
        }

        return $reservations;
    }

    private function equipmentQuantities(array $data): array
    {
        $quantities = $data['equipment_quantities'] ?? array_fill_keys($data['equipment_ids'] ?? [], 1);
        return collect($quantities)->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])->all();
    }

    private function normalizeRooms(array $data): array
    {
        $ids = array_key_exists('room_ids', $data) ? $data['room_ids'] : (!empty($data['resource_id']) ? [$data['resource_id']] : []);
        if (!is_array($ids) || !$ids || count($ids) > 50) {
            throw new \InvalidArgumentException('Select between 1 and 50 rooms.');
        }
        foreach ($ids as $id) {
            if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
                throw new \InvalidArgumentException('Select valid rooms.');
            }
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        $data['room_ids'] = $ids;
        $data['resource_id'] = $ids[0]; // Legacy clients continue to receive a primary room.
        return $data;
    }

    private function equipmentPivot(array $quantities): array
    {
        return collect($quantities)->mapWithKeys(fn ($quantity, $id) => [$id => ['quantity' => $quantity]])->all();
    }

    private function lockResources(int|array|null $roomId, array $equipment): void
    {
        $ids = array_values(array_unique(array_merge((array) $roomId, array_keys($equipment))));
        sort($ids);
        Resource::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
    }

    public function restoreReservation(ResourceReservation $reservation): ResourceReservation
    {
        return DB::transaction(function () use ($reservation) {
            $equipment = $reservation->equipment()->pluck('resource_reservation_items.quantity', 'resources.id')->all();
            $this->lockResources($reservation->roomIds(), $equipment);
            $this->validateRequestResources($reservation->roomIds(), $equipment);
            $reservation->restore();
            $reservation->update([
                'status' => 'pending', 'approved_by' => null, 'approved_at' => null,
                'approval_note' => null, 'google_event_id' => null,
                'finished_confirmed_at' => null, 'finished_confirmed_by' => null,
            ]);
            return $reservation;
        });
    }

    public function update(ResourceReservation $reservation, array $data): ResourceReservation
    {
        if (array_key_exists('room_ids', $data) || array_key_exists('resource_id', $data)) {
            $data = $this->normalizeRooms($data);
        }
        $payload = array_merge([
            'user_id' => $reservation->user_id,
            'requester_email' => $reservation->requester_email,
            'resource_id' => $reservation->resource_id,
            'room_ids' => $reservation->roomIds(),
            'equipment_ids' => $reservation->equipment()->pluck('resources.id')->all(),
            'equipment_quantities' => $reservation->equipment()->pluck('resource_reservation_items.quantity', 'resources.id')->all(),
            'title' => $reservation->title,
            'description' => $reservation->description,
            'start_datetime' => $reservation->start_datetime,
            'end_datetime' => $reservation->end_datetime,
            'notes' => $reservation->notes,
            'attachment_path' => $reservation->attachment_path,
            'number_of_pax' => $reservation->number_of_pax,
            'setup_arrangement' => $reservation->setup_arrangement,
            'contact_number' => $reservation->contact_number,
            'consumables' => $reservation->consumables,
            'floor_plan_path' => $reservation->floor_plan_path,
            'gate_pass_path' => $reservation->gate_pass_path,
        ], $data);
        if (array_key_exists('equipment_ids', $data) && !array_key_exists('equipment_quantities', $data)) {
            unset($payload['equipment_quantities']);
        }

        $wasApproved = $reservation->status === 'approved';
        $hadGoogleEvent = $wasApproved && (bool) $reservation->google_event_id;

        $equipment = $this->equipmentQuantities($payload);
        DB::transaction(function () use ($reservation, $payload, $equipment, $wasApproved) {
            $this->lockResources($payload['room_ids'], $equipment);
            if ($wasApproved) {
                $this->validateAvailability($payload['room_ids'], $equipment, $payload['start_datetime'], $payload['end_datetime'], $reservation->id);
            } else {
                $this->validateRequestResources($payload['room_ids'], $equipment);
            }
        });

        if ($wasApproved && $reservation->google_event_id && !$this->deleteGoogleCalendarEvent($reservation)) {
            throw new \Exception('Unable to delete the existing Google Calendar event. Reservation was not updated.');
        }

        $reservation = DB::transaction(function () use ($reservation, $payload, $equipment, $wasApproved) {
            $this->lockResources($payload['room_ids'], $equipment);
            if ($wasApproved) {
                $this->validateAvailability($payload['room_ids'], $equipment, $payload['start_datetime'], $payload['end_datetime'], $reservation->id);
            } else {
                $this->validateRequestResources($payload['room_ids'], $equipment);
            }

            $before = $reservation->only(['resource_id', 'title', 'start_datetime', 'end_datetime', 'notes', 'number_of_pax', 'setup_arrangement', 'contact_number', 'consumables', 'floor_plan_path', 'gate_pass_path']);
            $before['room_ids'] = $reservation->roomIds();
            $before['equipment_quantities'] = $reservation->equipment()->pluck('resource_reservation_items.quantity', 'resources.id')->all();
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
                'number_of_pax' => $payload['number_of_pax'] ?? null,
                'setup_arrangement' => $payload['setup_arrangement'] ?? null,
                'contact_number' => $payload['contact_number'] ?? null,
                'consumables' => $payload['consumables'] ?? null,
                'floor_plan_path' => $payload['floor_plan_path'] ?? null,
                'gate_pass_path' => $payload['gate_pass_path'] ?? null,
            ]);

            $reservation->rooms()->sync($payload['room_ids']);
            $reservation->equipment()->sync($this->equipmentPivot($equipment));

            $after = $reservation->fresh()->only(array_diff(array_keys($before), ['equipment_quantities', 'room_ids']));
            $after['room_ids'] = $payload['room_ids'];
            $after['equipment_quantities'] = $equipment;

            if (json_encode($before) !== json_encode($after)) DB::table('resource_reservation_edits')->insert([
                'reservation_id' => $reservation->id,
                'edited_by' => auth()->id(),
                'before' => json_encode($before),
                'after' => json_encode($after),
                'email_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $reservation->fresh(['resource', 'rooms', 'equipment']);
        });

        if ($hadGoogleEvent) {
            $this->recreateGoogleCalendarEvent($reservation);
        }

        return $reservation->fresh(['resource', 'rooms', 'equipment']);
    }

    public function approveReservation(
        ResourceReservation $reservation,
        int $approverId,
        ?string $note = null
    ): ResourceReservation {

        $reservation = DB::transaction(function () use ($reservation, $approverId, $note) {
            $reservation->load('equipment');
            $equipment = $reservation->equipment->mapWithKeys(fn ($item) => [$item->id => $item->pivot->quantity])->all();
            $this->lockResources($reservation->roomIds(), $equipment);
            $reservation->refresh()->load('equipment');
            if ($reservation->status === 'approved') {
                throw new \Exception('Reservation is already approved.');
            }
            $equipment = $reservation->equipment->mapWithKeys(fn ($item) => [$item->id => $item->pivot->quantity])->all();
            $this->validateAvailability($reservation->roomIds(), $equipment, $reservation->start_datetime, $reservation->end_datetime, $reservation->id);
            $reservation->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'approval_note' => $note,
                'google_event_id' => null,
            ]);
            return $reservation;
        });

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

        if ($googleEventId) {
            $reservation->update(['google_event_id' => $googleEventId]);
        }

        $reservation->load(['resource', 'rooms', 'equipment']);

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

        $reservation->load(['resource', 'rooms', 'equipment']);

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
