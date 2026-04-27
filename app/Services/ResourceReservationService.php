<?php

namespace App\Services;

use App\Models\ResourceReservation;
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
        // Check main resource (room)
        if ($primaryResourceId) {
            if (!$this->isResourceAvailable($primaryResourceId, $start, $end)) {
                throw new \Exception('Selected resource is not available for the chosen time.');
            }
        }

        // Check equipment
        foreach ($equipmentIds as $equipmentId) {
            if (!$this->isResourceAvailable($equipmentId, $start, $end)) {
                throw new \Exception('One of the selected equipment is not available.');
            }
        }
    }

    /**
     * Create reservation safely
     */
    public function create(array $data): ResourceReservation
    {
        return DB::transaction(function () use ($data) {

            $this->validateAvailability(
                $data['resource_id'] ?? null,
                $data['equipment_ids'] ?? [],
                $data['start_datetime'],
                $data['end_datetime']
            );

            $reservation = ResourceReservation::create([
                'user_id' => $data['user_id'] ?? auth()->id(),
                'resource_id' => $data['resource_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_datetime' => $data['start_datetime'],
                'end_datetime' => $data['end_datetime'],
                'status' => 'pending',
            ]);

            // Attach equipment
            if (!empty($data['equipment_ids'])) {
                $reservation->equipment()->sync($data['equipment_ids']);
            }

            return $reservation;
        });
    }
}
