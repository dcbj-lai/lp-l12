<?php

namespace App\Http\Controllers;

use App\Models\ResourceReservation;
use App\Services\ResourceReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FacilityReservationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'all'])],
            'resource_id' => [
                'nullable',
                'integer',
                Rule::exists('resources', 'id')->where(fn ($query) => $query->where('type', 'room')),
            ],
            'requester_email' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = $validated['status'] ?? 'all';
        $search = mb_strtolower(trim((string) ($validated['search'] ?? '')));
        $perPage = (int) ($validated['per_page'] ?? 25);

        $reservations = ResourceReservation::query()
            ->with(['resource', 'equipment', 'approver', 'user'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when(isset($validated['resource_id']), function ($query) use ($validated) {
                $query->where(function ($query) use ($validated) {
                    $query->where('resource_id', $validated['resource_id'])
                        ->orWhereHas('equipment', fn ($equipment) => $equipment->whereKey($validated['resource_id']));
                });
            })
            ->when(!empty($validated['requester_email']), function ($query) use ($validated) {
                $query->whereRaw('LOWER(requester_email) LIKE ?', ['%' . mb_strtolower($validated['requester_email']) . '%']);
            })
            ->when(!empty($validated['date_from']), fn ($query) => $query->where('end_datetime', '>=', CarbonImmutable::parse($validated['date_from'])->startOfDay()))
            ->when(!empty($validated['date_to']), fn ($query) => $query->where('start_datetime', '<=', CarbonImmutable::parse($validated['date_to'])->endOfDay()))
            ->when($search !== '', function ($query) use ($search) {
                $like = "%{$search}%";

                $query->where(function ($query) use ($like) {
                    $query->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(requester_email) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(notes) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(approval_note) LIKE ?', [$like])
                        ->orWhereHas('resource', fn ($resource) => $resource->whereRaw('LOWER(name) LIKE ?', [$like]))
                        ->orWhereHas('equipment', fn ($equipment) => $equipment->whereRaw('LOWER(name) LIKE ?', [$like]));
                });
            })
            ->orderByDesc('start_datetime')
            ->paginate($perPage);

        return response()->json([
            'data' => $reservations->getCollection()
                ->map(fn (ResourceReservation $reservation) => $this->reservationPayload($reservation))
                ->values(),
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'from' => $reservations->firstItem(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'to' => $reservations->lastItem(),
                'total' => $reservations->total(),
            ],
        ]);
    }

    public function store(Request $request, ResourceReservationService $service)
    {
        $payload = $this->validatedReservationPayload($request);

        try {
            $reservation = $service->create($payload);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'data' => $this->reservationPayload($reservation),
        ], 201);
    }

    public function show(ResourceReservation $reservation)
    {
        return response()->json([
            'data' => $this->reservationPayload($reservation->load(['resource', 'equipment', 'approver', 'user'])),
        ]);
    }

    public function update(Request $request, ResourceReservation $reservation, ResourceReservationService $service)
    {
        $payload = $this->validatedReservationPayload($request, partial: true, reservation: $reservation);

        try {
            $reservation = $service->update($reservation, $payload);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => $this->reservationPayload($reservation->fresh(['resource', 'equipment', 'approver', 'user'])),
            ], 409);
        }

        return response()->json([
            'data' => $this->reservationPayload($reservation),
        ]);
    }

    public function destroy(ResourceReservation $reservation, ResourceReservationService $service)
    {
        if ($reservation->google_event_id && !$service->deleteGoogleCalendarEvent($reservation)) {
            return response()->json([
                'message' => 'Unable to delete the Google Calendar event. Reservation was not deleted.',
                'data' => $this->reservationPayload($reservation->fresh(['resource', 'equipment', 'approver', 'user'])),
            ], 409);
        }

        if ($reservation->google_event_id === null) {
            $reservation->save();
        }

        if ($reservation->attachment_path) {
            try {
                Storage::disk('s3')->delete($reservation->attachment_path);
            } catch (\Throwable $e) {
                Log::warning('Failed to delete resource reservation attachment through API', [
                    'reservation_id' => $reservation->id,
                    'path' => $reservation->attachment_path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $reservation->delete();

        return response()->noContent();
    }

    public function approve(Request $request, ResourceReservation $reservation, ResourceReservationService $service)
    {
        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $reservation = $service->approveReservation(
                $reservation,
                $request->user()->id,
                $validated['approval_note'] ?? null
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'data' => $this->reservationPayload($reservation),
        ]);
    }

    public function reject(Request $request, ResourceReservation $reservation, ResourceReservationService $service)
    {
        $validated = $request->validate([
            'approval_note' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $reservation = $service->rejectReservation(
                $reservation,
                $request->user()->id,
                $validated['approval_note']
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => $this->reservationPayload($reservation->fresh(['resource', 'equipment', 'approver', 'user'])),
            ], 409);
        }

        return response()->json([
            'data' => $this->reservationPayload($reservation),
        ]);
    }

    public function cleanupGoogleCalendar(ResourceReservation $reservation, ResourceReservationService $service)
    {
        $eventId = $reservation->google_event_id;

        if (!$eventId) {
            return response()->json([
                'cleaned' => false,
                'message' => 'Reservation has no Google Calendar event ID.',
                'data' => $this->reservationPayload($reservation->load(['resource', 'equipment', 'approver', 'user'])),
            ]);
        }

        if (!$service->deleteGoogleCalendarEvent($reservation)) {
            return response()->json([
                'cleaned' => false,
                'message' => 'Unable to delete the Google Calendar event.',
                'data' => $this->reservationPayload($reservation->fresh(['resource', 'equipment', 'approver', 'user'])),
            ], 409);
        }

        $reservation->save();

        return response()->json([
            'cleaned' => true,
            'deleted_google_event_id' => $eventId,
            'data' => $this->reservationPayload($reservation->fresh(['resource', 'equipment', 'approver', 'user'])),
        ]);
    }

    protected function validatedReservationPayload(
        Request $request,
        bool $partial = false,
        ?ResourceReservation $reservation = null
    ): array {
        $required = $partial ? 'sometimes' : 'required';

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'requester_email' => [$partial ? 'sometimes' : 'required_without:user_id', 'nullable', 'email', 'max:255'],
            'resource_id' => ['nullable', 'integer', 'exists:resources,id'],
            'equipment_ids' => [$partial ? 'sometimes' : 'nullable', 'array'],
            'equipment_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('resources', 'id')->where(fn ($query) => $query->where('type', 'equipment')),
            ],
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_datetime' => [$required, 'date'],
            'end_datetime' => [$required, 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment_path' => ['nullable', 'string', 'max:2048'],
        ]);

        $start = CarbonImmutable::parse($validated['start_datetime'] ?? $reservation?->start_datetime);
        $end = CarbonImmutable::parse($validated['end_datetime'] ?? $reservation?->end_datetime);

        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'end_datetime' => ['The end datetime must be after the start datetime.'],
            ]);
        }

        return $validated;
    }

    protected function reservationPayload(ResourceReservation $reservation): array
    {
        $reservation->loadMissing(['resource', 'equipment', 'approver', 'user']);

        return [
            'id' => $reservation->id,
            'title' => $reservation->title,
            'description' => $reservation->description,
            'status' => $reservation->status,
            'requester_email' => $reservation->requester_email,
            'user' => $reservation->user ? [
                'id' => $reservation->user->id,
                'name' => $reservation->user->name,
                'email' => $reservation->user->email,
            ] : null,
            'resource' => $reservation->resource ? [
                'id' => $reservation->resource->id,
                'name' => $reservation->resource->name,
                'type' => $reservation->resource->type,
                'location' => $reservation->resource->location,
                'capacity' => $reservation->resource->capacity,
                'control_number' => $reservation->resource->control_number,
            ] : null,
            'equipment' => $reservation->equipment
                ->map(fn ($resource) => [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'type' => $resource->type,
                    'control_number' => $resource->control_number,
                ])
                ->values(),
            'start_datetime' => $reservation->start_datetime?->toISOString(),
            'end_datetime' => $reservation->end_datetime?->toISOString(),
            'notes' => $reservation->notes,
            'attachment_path' => $reservation->attachment_path,
            'approval_note' => $reservation->approval_note,
            'approved_by' => $reservation->approved_by,
            'approved_at' => $reservation->approved_at?->toISOString(),
            'approver' => $reservation->approver ? [
                'id' => $reservation->approver->id,
                'name' => $reservation->approver->name,
                'email' => $reservation->approver->email,
            ] : null,
            'google_event_id' => $reservation->google_event_id,
            'created_at' => $reservation->created_at?->toISOString(),
            'updated_at' => $reservation->updated_at?->toISOString(),
        ];
    }
}
