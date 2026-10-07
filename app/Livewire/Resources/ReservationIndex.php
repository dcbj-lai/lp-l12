<?php

namespace App\Livewire\Resources;

use App\Mail\ResourceBookingEdited;
use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Services\ResourceReservationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Resource Reservations')]
class ReservationIndex extends Component
{
    use WithFileUploads;

    public bool $showBulkReminderModal = false;
    #[\Livewire\Attributes\Locked]
    public array $bulkReminderIds = [];

    public function selectBulkReminders(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $this->bulkReminderIds = ResourceReservation::where('status', 'approved')->where('billing_status', 'billed')
            ->whereNull('paid_at')->whereNotNull('soa_path')->whereNotNull('payment_due_at')
            ->where('soa_email_pending', false)->whereNotNull('requester_email')
            ->whereDoesntHave('billingEmails', fn ($q) => $q->where('status', 'queued'))
            ->whereDoesntHave('billingEmails', fn ($q) => $q->whereIn('kind', ['upcoming', 'due_today', 'overdue'])->where('sent_at', '>=', today()))
            ->get()->filter(fn ($r) => filter_var($r->requester_email, FILTER_VALIDATE_EMAIL))->pluck('id')->all();
        $this->showBulkReminderModal = true;
    }

    public function cancelBulkReminders(): void
    {
        $this->showBulkReminderModal = false;
        $this->bulkReminderIds = [];
    }

    public function sendBulkReminders(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        if (!$this->showBulkReminderModal) { return; }
        $sent = 0;
        $skipped = 0;
        foreach ($this->bulkReminderIds as $id) {
            try {
                app(\App\Services\FacilityBillingEmailService::class)->queue($id, 'reminder');
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $skipped++;
            }
        }
        $this->cancelBulkReminders();
        $this->dispatch('flash', type: 'success', message: "Payment reminders queued: {$sent}. Skipped or failed: {$skipped}.");
    }

    public string $statusFilter = 'pending';
    public string $eventDateSort = 'asc';
    public string $requestDateSort = 'desc';
    public ?string $selectedSeriesId = null;
    public ?int $reservationId = null;
    public ?int $conflictReservationId = null;
    public ?string $approvalNote = null;
    public ?int $deleteId = null;
    public bool $showDeleteModal = false;
    public bool $showDeleteSeriesModal = false;
    #[\Livewire\Attributes\Locked]
    public array $deleteSeriesIds = [];
    #[\Livewire\Attributes\Locked]
    public ?string $deleteSeriesId = null;

    public function selectSeriesForDelete(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        abort_unless($this->selectedSeriesId, 422);
        $this->deleteSeriesId = $this->selectedSeriesId;
        $this->deleteSeriesIds = ResourceReservation::where('recurrence_series_id', $this->deleteSeriesId)->pluck('id')->all();
        $this->showDeleteSeriesModal = count($this->deleteSeriesIds) > 0;
    }

    public function getSeriesDeletionReservationsProperty()
    {
        return ResourceReservation::where('recurrence_series_id', $this->deleteSeriesId)
            ->whereIn('id', $this->deleteSeriesIds)->orderBy('start_datetime')->get();
    }

    public function cancelSeriesDelete(): void
    {
        $this->reset('showDeleteSeriesModal', 'deleteSeriesId', 'deleteSeriesIds');
    }

    public function confirmSeriesDelete(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        if (!$this->showDeleteSeriesModal || !$this->deleteSeriesId || !$this->deleteSeriesIds) return;
        $deleted = 0;
        $protected = 0;
        $failed = [];
        foreach ($this->seriesDeletionReservations as $reservation) {
            try {
                $result = DB::transaction(function () use ($reservation) {
                    $current = ResourceReservation::whereKey($reservation->id)->lockForUpdate()->first();
                    if (!$current) return 'gone';
                    if ($current->soa_path) return 'protected';
                    if ($current->google_event_id && !app(ResourceReservationService::class)->deleteGoogleCalendarEvent($current)) {
                        throw new \RuntimeException('Calendar event could not be removed.');
                    }
                    if ($current->isDirty('google_event_id')) $current->save();
                    $current->delete();
                    return 'deleted';
                });
                if ($result === 'deleted') $deleted++;
                if ($result === 'protected') $protected++;
            } catch (\Throwable $e) {
                $failed[] = '#' . $reservation->id . ': ' . $e->getMessage();
            }
        }
        $this->cancelSeriesDelete();
        $this->modal('recurring-series')->close();
        $message = "Deleted {$deleted} dates. {$protected} dates protected by SOA.";
        if ($failed) $message .= ' Not deleted: ' . implode(' | ', $failed);
        $this->dispatch('flash', type: $failed ? 'error' : 'success', message: $message);
    }
    public ?int $editId = null;
    public string $editTitle = '';
    public ?int $editRoomId = null;
    public array $editRoomIds = [];
    public string $editStart = '';
    public string $editEnd = '';
    public ?int $editPax = null;
    public string $editSetup = '';
    public string $editContact = '';
    public string $editNotes = '';
    public string $editConsumables = '';
    public array $editEquipment = [];
    public ?int $editEquipmentToAdd = null;
    public $editFloorPlan;
    public $editGatePass;
    public ?int $billingId = null;
    public ?int $removeSoaId = null;
    public bool $showRemoveSoaModal = false;
    public $soaFile;
    public string $soaReplacementReason = '';
    public bool $replacingSoa = false;
    public bool $replacingPaidSoa = false;
    public bool $billingFromDone = false;
    public string $soaSentDate = '';
    public ?int $billingEmailId = null;
    public string $billingEmailAction = 'soa';
    public bool $showBillingEmailModal = false;
    public ?int $paymentId = null;
    public string $paymentDate = '';
    public $paymentProof;
    public array $paymentProofs = [];
    public ?int $finishId = null;

    public function confirmFinishedEvent(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        try {
            DB::transaction(function () {
                $reservation = ResourceReservation::whereKey($this->finishId)->lockForUpdate()->firstOrFail();
                if ($reservation->status !== 'approved' || $reservation->end_datetime->isFuture()
                    || $reservation->soa_path || $reservation->billing_status !== 'unbilled') {
                    throw new \RuntimeException('Only finished approved events without an SOA or billing can be confirmed as requiring no payment.');
                }
                $reservation->update(['finished_confirmed_at' => now(), 'finished_confirmed_by' => auth()->id()]);
            });
            $this->reset('finishId');
            $this->modal('finish-reservation')->close();
            $this->dispatch('flash', type: 'success', message: 'Finished event moved to Done.');
        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
    }

    public function mount(): void
    {
        abort_if(auth()->user()->hasRole('facility.viewer'), 403);
        abort_unless(auth()->user()->hasAnyRole(['facility.admin', 'facility.approver']), 403);
    }

    public function getReservationsProperty()
    {
        $byRequestDate = $this->statusFilter === 'pending';
        $direction = ($byRequestDate ? $this->requestDateSort : $this->eventDateSort) === 'desc' ? 'desc' : 'asc';
        $dateColumn = $byRequestDate ? 'created_at' : 'start_datetime';

        return ResourceReservation::with(['resource', 'rooms', 'equipment', 'items.resource', 'latestBillingEmail', 'latestPaymentReminder', 'paymentProofs', 'soaRevisions'])
            ->when($this->statusFilter === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($this->statusFilter === 'archive', fn ($query) => $query->archived())
            ->when(in_array($this->statusFilter, ['billed', 'paid']), fn ($query) => $query->where('status', 'approved')->where('billing_status', $this->statusFilter))
            ->when(in_array($this->statusFilter, ['approved', 'billed', 'paid']), fn ($query) => $query->whereNotIn('id', ResourceReservation::archived()->select('id')))
            ->when(in_array($this->statusFilter, ['pending', 'approved', 'rejected']), fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy($dateColumn, $direction)->orderBy('id', $direction)->get()
            ->groupBy(fn ($reservation) => $reservation->{$dateColumn}->format('F Y'));
    }

    public function getBookingGroupsProperty()
    {
        $byRequestDate = $this->statusFilter === 'pending';
        $showIndividualOccurrences = in_array($this->statusFilter, ['approved', 'rejected', 'billed', 'paid', 'archive'], true);
        $reservations = $this->reservations->flatten(1);

        $groups = $reservations
            ->groupBy(fn ($reservation) => !$showIndividualOccurrences && $reservation->recurrence_series_id ? $reservation->recurrence_series_id : 'single-' . $reservation->id)
            ->map(function ($occurrences, $key) use ($byRequestDate, $showIndividualOccurrences) {
                $ordered = $occurrences->sortBy('start_datetime')->values();
                $upcoming = $ordered->first(fn ($reservation) => $reservation->start_datetime->gte(now()));
                $representative = $upcoming ?? $ordered->last();
                $requestDate = $occurrences->max('created_at');
                $conflicts = $occurrences->mapWithKeys(fn ($reservation) => [$reservation->id => $this->conflictsFor($reservation)])
                    ->filter(fn ($names) => $names);

                return [
                    'key' => $key,
                    'is_series' => !$showIndividualOccurrences && (bool) $representative->recurrence_series_id,
                    'series_id' => $representative->recurrence_series_id,
                    'representative' => $representative,
                    'display_date' => $byRequestDate ? $requestDate : $representative->start_datetime,
                    'has_upcoming' => (bool) $upcoming,
                    'matched_count' => $occurrences->count(),
                    'status_counts' => $occurrences->countBy('status'),
                    'conflict_count' => $conflicts->count(),
                    'conflict_names' => $conflicts->flatten()->unique()->values()->all(),
                ];
            })->values();

        $direction = ($byRequestDate ? $this->requestDateSort : $this->eventDateSort) === 'desc' ? -1 : 1;
        return $groups->sort(function ($a, $b) use ($byRequestDate, $direction, $showIndividualOccurrences) {
            if (!$byRequestDate && !$showIndividualOccurrences && $a['has_upcoming'] !== $b['has_upcoming']) {
                return $a['has_upcoming'] ? -1 : 1;
            }
            return $direction * ($a['display_date']->timestamp <=> $b['display_date']->timestamp)
                ?: $direction * ($a['representative']->id <=> $b['representative']->id);
        })->values()->groupBy(fn ($group) => $group['display_date']->format('F Y'));
    }

    public function conflictsFor(ResourceReservation $reservation): array
    {
        return app(ResourceReservationService::class)->approvedConflictsForReservation($reservation);
    }

    public function showConflictManager(int $id): void
    {
        $reservation = ResourceReservation::findOrFail($id);
        abort_unless($reservation->status === 'pending', 404);
        $this->conflictReservationId = $id;
        $this->modal('conflict-manager')->show();
    }

    public function getConflictRequestProperty(): ?ResourceReservation
    {
        return $this->conflictReservationId
            ? ResourceReservation::with(['resource', 'rooms', 'equipment'])->find($this->conflictReservationId)
            : null;
    }

    public function getBlockingBookingsProperty()
    {
        $request = $this->conflictRequest;
        return $request ? app(ResourceReservationService::class)->blockingApprovedReservations($request) : collect();
    }

    public function editConflictingBooking(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        abort_unless($this->blockingBookings->contains('id', $id), 404);
        $this->modal('conflict-manager')->close();
        $this->selectForEdit($id);
        $this->modal('edit-reservation')->show();
    }

    public function rejectConflictingBooking(int $id): void
    {
        abort_unless($this->blockingBookings->contains('id', $id), 404);
        $this->modal('conflict-manager')->close();
        $this->selectForDecision($id);
        $this->modal('reject-reservation')->show();
    }

    public function editConflictedRequest(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $request = $this->conflictRequest;
        abort_unless($request && $request->status === 'pending', 404);
        $this->modal('conflict-manager')->close();
        $this->selectForEdit($request->id);
        $this->modal('edit-reservation')->show();
    }

    public function getSelectedSeriesReservationsProperty()
    {
        if (!$this->selectedSeriesId) {
            return collect();
        }

        return ResourceReservation::withTrashed()->with(['resource', 'rooms', 'equipment', 'latestBillingEmail', 'latestPaymentReminder', 'paymentProofs', 'soaRevisions'])
            ->where('recurrence_series_id', $this->selectedSeriesId)
            ->orderBy('start_datetime')->get();
    }

    public function getUnsentEditCountsProperty(): array
    {
        return DB::table('resource_reservation_edits')->where('email_available', true)->whereNull('email_queued_at')
            ->selectRaw('reservation_id, count(*) as edits_count')->groupBy('reservation_id')
            ->pluck('edits_count', 'reservation_id')->all();
    }

    public function emailReservationChanges(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::with(['resource', 'rooms', 'equipment'])->findOrFail($id);
        $edits = DB::table('resource_reservation_edits')->where('reservation_id', $id)
            ->where('email_available', true)->whereNull('email_queued_at')->orderBy('id')->get();
        if ($edits->isEmpty()) {
            $this->dispatch('flash', type: 'error', message: 'There are no unsent changes for this reservation.');
            return;
        }

        $before = json_decode($edits->first()->before, true) ?: [];
        $after = json_decode($edits->last()->after, true) ?: [];
        foreach (['before', 'after'] as $snapshot) {
            if (!array_key_exists('room_ids', ${$snapshot})) {
                ${$snapshot}['room_ids'] = !empty(${$snapshot}['resource_id']) ? [${$snapshot}['resource_id']] : [];
            }
        }
        $labels = [
            'title' => 'Event name', 'room_ids' => 'Rooms', 'start_datetime' => 'Start',
            'end_datetime' => 'End', 'number_of_pax' => 'Number of pax',
            'setup_arrangement' => 'Setup arrangement', 'contact_number' => 'Contact number',
            'notes' => 'Notes', 'consumables' => 'Consumables',
            'equipment_quantities' => 'Equipment', 'floor_plan_path' => 'Floor plan',
            'gate_pass_path' => 'Gate pass',
        ];
        $changes = [];
        foreach ($labels as $field => $label) {
            if (($before[$field] ?? null) == ($after[$field] ?? null)) {
                continue;
            }
            $changes[] = [
                'label' => $label,
                'before' => $this->formatEditValue($field, $before[$field] ?? null, false),
                'after' => $this->formatEditValue($field, $after[$field] ?? null, true),
            ];
        }
        if (!$changes) {
            DB::table('resource_reservation_edits')->whereIn('id', $edits->pluck('id'))->update(['email_queued_at' => now()]);
            $this->dispatch('flash', type: 'success', message: 'No net changes remain to email.');
            return;
        }
        if (!$reservation->requester_email) {
            $this->dispatch('flash', type: 'error', message: 'This reservation has no requester email address.');
            return;
        }
        try {
            Mail::to($reservation->requester_email)->queue(new ResourceBookingEdited($reservation, $changes));
            DB::table('resource_reservation_edits')->whereIn('id', $edits->pluck('id'))->update(['email_queued_at' => now()]);
            $this->dispatch('flash', type: 'success', message: 'Reservation changes queued for email to the requester.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('flash', type: 'error', message: 'The change email could not be queued. Please try again.');
        }
    }

    private function formatEditValue(string $field, mixed $value, bool $isAfter): string
    {
        if ($value === null || $value === '' || $value === []) {
            return 'None';
        }
        if ($field === 'resource_id') {
            return Resource::find($value)?->name ?? 'Room #' . $value;
        }
        if ($field === 'room_ids') {
            return Resource::whereIn('id', (array) $value)->orderBy('name')->pluck('name')->join(', ') ?: 'None';
        }
        if ($field === 'equipment_quantities') {
            $names = Resource::whereIn('id', array_keys((array) $value))->pluck('name', 'id');
            return collect($value)->map(fn ($quantity, $id) => ($names[$id] ?? 'Equipment #' . $id) . ' × ' . $quantity)->join(', ') ?: 'None';
        }
        if (in_array($field, ['start_datetime', 'end_datetime'], true)) {
            return \Carbon\Carbon::parse($value)->format('M j, Y g:i A');
        }
        if (in_array($field, ['floor_plan_path', 'gate_pass_path'], true)) {
            return $isAfter ? 'Updated attachment' : 'Previous attachment';
        }
        return (string) $value;
    }

    public function showRecurringSeries(string $seriesId): void
    {
        $this->selectedSeriesId = $seriesId;
        $this->modal('recurring-series')->show();
    }

    public function getRoomsProperty()
    {
        return Resource::where('type', 'room')->where('capacity', '>', 0)->get()
            ->sort(fn ($a, $b) => ($a->floorSortKey() <=> $b->floorSortKey()) ?: strcmp($a->name, $b->name))->values();
    }

    public function getEquipmentProperty()
    {
        return Resource::where('type', 'equipment')->where('total_quantity', '>', 0)->orderBy('name')->get();
    }

    public function selectForDecision(int $id): void
    {
        $this->reservationId = $id;
        $this->approvalNote = null;
    }

    public function getDecisionConflictsProperty(): array
    {
        if (!$this->reservationId) {
            return [];
        }
        $reservation = ResourceReservation::with('equipment')->find($this->reservationId);
        return $reservation ? $this->conflictsFor($reservation) : [];
    }

    public function confirmApprove(): void
    {
        try {
            app(ResourceReservationService::class)->approveReservation(ResourceReservation::findOrFail($this->reservationId), auth()->id(), $this->approvalNote);
            $this->reset('reservationId', 'approvalNote');
            $this->modal('approve-reservation')->close();
            $this->dispatch('flash', type: 'success', message: 'Reservation approved.');
        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
    }

    public function approvePendingSeries(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['facility.admin', 'facility.approver']), 403);
        abort_unless($this->selectedSeriesId, 422);

        $approved = 0;
        $blocked = [];
        $pending = ResourceReservation::where('recurrence_series_id', $this->selectedSeriesId)
            ->where('status', 'pending')->orderBy('start_datetime')->get();

        foreach ($pending as $reservation) {
            try {
                app(ResourceReservationService::class)->approveReservation($reservation, auth()->id());
                $approved++;
            } catch (\Throwable $e) {
                $blocked[] = $reservation->start_datetime->format('M j') . ': ' . $e->getMessage();
            }
        }

        $this->modal('approve-series')->close();
        $message = "Approved {$approved} of {$pending->count()} pending dates.";
        if ($blocked) {
            $message .= ' Still pending: ' . implode(' | ', $blocked);
        }
        $this->dispatch('flash', type: $blocked ? 'error' : 'success', message: $message);
    }

    public function confirmReject(): void
    {
        $this->validate(['approvalNote' => 'required|string|max:2000']);
        try {
            app(ResourceReservationService::class)->rejectReservation(ResourceReservation::findOrFail($this->reservationId), auth()->id(), $this->approvalNote);
            $this->reset('reservationId', 'approvalNote');
            $this->modal('reject-reservation')->close();
            $this->dispatch('flash', type: 'success', message: 'Reservation rejected.');
        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
    }

    public function selectForDelete(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::findOrFail($id);
        if ($reservation->soa_path) {
            $this->dispatch('flash', type: 'error', message: 'Remove the SOA before deleting this reservation.');
            return;
        }
        $this->deleteId = $reservation->id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
        $this->showDeleteModal = false;
    }

    public function confirmDelete(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        if ($this->deleteId === null) return;
        try {
            $reservation = ResourceReservation::findOrFail($this->deleteId);
            if ($reservation->soa_path) {
                throw new \RuntimeException('Remove the SOA before deleting this reservation.');
            }
            if ($reservation->google_event_id && !app(ResourceReservationService::class)->deleteGoogleCalendarEvent($reservation)) {
                throw new \RuntimeException('Unable to remove the calendar event. Reservation was not deleted.');
            }
            if ($reservation->isDirty('google_event_id')) {
                $reservation->save();
            }
            $reservation->delete();
            $this->deleteId = null;
            $this->showDeleteModal = false;
            $this->dispatch('flash', type: 'success', message: 'Reservation deleted.');
        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
    }

    public function restoreReservation(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        try {
            $reservation = ResourceReservation::onlyTrashed()->findOrFail($id);
            app(ResourceReservationService::class)->restoreReservation($reservation);
            $this->dispatch('flash', type: 'success', message: 'Reservation restored for approval.');
        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
    }

    public function restoreDeletedSeries(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        abort_unless($this->selectedSeriesId, 422);

        $restored = 0;
        $blocked = [];
        $deleted = ResourceReservation::onlyTrashed()->where('recurrence_series_id', $this->selectedSeriesId)
            ->orderBy('start_datetime')->get();
        foreach ($deleted as $reservation) {
            try {
                app(ResourceReservationService::class)->restoreReservation($reservation);
                $restored++;
            } catch (\Throwable $e) {
                $blocked[] = $reservation->start_datetime->format('M j') . ': ' . $e->getMessage();
            }
        }

        $this->modal('restore-series')->close();
        $message = "Restored {$restored} of {$deleted->count()} deleted dates for approval.";
        if ($blocked) {
            $message .= ' Not restored: ' . implode(' | ', $blocked);
        }
        $this->dispatch('flash', type: $blocked ? 'error' : 'success', message: $message);
    }

    public function selectForEdit(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::with('equipment')->findOrFail($id);
        $this->editId = $id;
        $this->editTitle = $reservation->title;
        $this->editRoomId = $reservation->resource_id;
        $this->editRoomIds = $reservation->roomIds();
        $this->editStart = $reservation->start_datetime->format('Y-m-d\TH:i');
        $this->editEnd = $reservation->end_datetime->format('Y-m-d\TH:i');
        $this->editPax = $reservation->number_of_pax;
        $this->editSetup = $reservation->setup_arrangement ?? '';
        $this->editContact = $reservation->contact_number ?? '';
        $this->editNotes = $reservation->notes ?? '';
        $this->editConsumables = $reservation->consumables ?? '';
        $this->editEquipment = $reservation->equipment->mapWithKeys(fn ($item) => [$item->id => $item->pivot->quantity])->all();
        $this->editEquipmentToAdd = null;
        $this->editFloorPlan = null;
        $this->editGatePass = null;
    }

    public function addEditEquipment(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        if ($this->editEquipmentToAdd === null) {
            return;
        }
        $item = Resource::where('type', 'equipment')->findOrFail($this->editEquipmentToAdd);
        $this->editEquipment[$item->id] ??= 1;
        $this->editEquipmentToAdd = null;
    }

    public function removeEditEquipment(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        unset($this->editEquipment[$id]);
    }

    public function saveEdit(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $this->validate([
            'editTitle' => 'required|string|max:255', 'editRoomIds' => 'required|array|min:1|max:50',
            'editRoomIds.*' => 'integer|distinct|exists:resources,id',
            'editStart' => 'required|date', 'editEnd' => 'required|date|after:editStart',
            'editPax' => 'nullable|integer|min:1', 'editSetup' => 'nullable|string|max:255',
            'editContact' => 'nullable|string|max:50', 'editNotes' => 'nullable|string|max:2000',
            'editConsumables' => 'nullable|string|max:2000', 'editEquipment.*' => 'nullable|integer|min:0',
            'editFloorPlan' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
            'editGatePass' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);
        $paths = [];
        try {
            $reservation = ResourceReservation::findOrFail($this->editId);
            foreach (['editFloorPlan' => 'floor_plan_path', 'editGatePass' => 'gate_pass_path'] as $property => $column) {
                if ($this->{$property}) {
                    $paths[$column] = $this->{$property}->storeAs('reservations', Str::uuid() . '.' . $this->{$property}->getClientOriginalExtension(), config('filesystems.facility_upload_disk'));
                }
            }
            $reservation = app(ResourceReservationService::class)->update($reservation, [
                'title' => $this->editTitle, 'room_ids' => $this->editRoomIds,
                'start_datetime' => $this->editStart, 'end_datetime' => $this->editEnd,
                'number_of_pax' => $this->editPax, 'setup_arrangement' => $this->editSetup,
                'contact_number' => $this->editContact, 'notes' => $this->editNotes,
                'consumables' => $this->editConsumables,
                'equipment_quantities' => array_filter($this->editEquipment, fn ($quantity) => (int) $quantity > 0),
            ] + $paths);
            $this->editId = null;
            $this->modal('edit-reservation')->close();
            $this->dispatch('flash', type: 'success', message: 'Reservation updated.');
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                \Storage::disk(config('filesystems.facility_upload_disk'))->delete($path);
            }
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
    }

    public function selectForBilling(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $this->resetValidation(['soaFile', 'soaReplacementReason']);
        $reservation = ResourceReservation::findOrFail($id);
        abort_unless($reservation->status === 'approved', 422);
        $this->replacingSoa = (bool) $reservation->soa_path;
        $this->replacingPaidSoa = $reservation->soa_locked;
        $this->billingFromDone = $reservation->is_archived;
        $this->soaReplacementReason = '';
        $this->billingId = $id;
        $this->soaSentDate = $reservation->soa_sent_at?->toDateString() ?? now()->toDateString();
        $this->soaFile = null;
        $this->modal('billing-reservation')->show();
    }

    public function updatedSoaFile(): void
    {
        $this->resetValidation('soaFile');
    }

    private function cleanFailedUpload(string $path): void
    {
        try {
            \Storage::disk(config('filesystems.facility_upload_disk'))->delete($path);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function markBilled(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::findOrFail($this->billingId);
        abort_unless($reservation->status === 'approved', 422);
        $this->soaReplacementReason = trim($this->soaReplacementReason);
        $this->validate([
            'soaFile' => 'required|file|max:10240|mimes:pdf,doc,docx',
            'soaReplacementReason' => ($reservation->soa_path ? 'required' : 'nullable').'|string|max:2000',
        ], [
            'soaFile.required' => 'Upload an SOA file.',
            'soaFile.mimes' => 'The SOA must be a PDF or Word file.',
            'soaFile.max' => 'The SOA must be 10 MB or smaller.',
            'soaReplacementReason.required' => 'Enter a reason for replacing the SOA.',
        ]);
        $oldPath = $reservation->soa_path;
        $path = null;
        try {
            \DB::transaction(function () use (&$reservation, &$oldPath, &$path) {
                $reservation = ResourceReservation::whereKey($this->billingId)->lockForUpdate()->firstOrFail();
                if ($reservation->status !== 'approved') {
                    throw new \RuntimeException('Only approved reservations can upload an SOA.');
                }
                $oldPath = $reservation->soa_path;
                if ($oldPath && trim($this->soaReplacementReason) === '') {
                    throw new \RuntimeException('A reason is required to replace the SOA.');
                }
                $path = $this->soaFile->storeAs('reservations/soa', Str::uuid() . '.' . $this->soaFile->getClientOriginalExtension(), config('filesystems.facility_upload_disk'));
                if (!$path || !\Storage::disk(config('filesystems.facility_upload_disk'))->exists($path)) {
                    throw new \RuntimeException('SOA upload failed. Please try again.');
                }
                if ($oldPath) {
                    $reservation->soaRevisions()->create([
                        'previous_path' => $oldPath, 'replacement_path' => $path,
                        'reason' => $this->soaReplacementReason, 'replaced_by' => auth()->id(),
                    ]);
                }
                $updates = ['soa_path' => $path, 'soa_email_pending' => !$reservation->soa_locked];
                if (!$reservation->soa_locked) {
                    $updates['payment_due_at'] = today()->addDays(15);
                    $updates['finished_confirmed_at'] = null;
                    $updates['finished_confirmed_by'] = null;
                }
                $reservation->update($updates);
            });
        } catch (\Throwable $e) {
            if ($path) {
                $this->cleanFailedUpload($path);
            }
            report($e);
            $this->addError('soaFile', $e instanceof \RuntimeException ? $e->getMessage() : 'SOA upload failed. Please try again.');
            return;
        }
        $this->modal('billing-reservation')->close();
        $destination = $reservation->is_archived ? 'archive'
            : (in_array($reservation->billing_status, ['billed', 'paid'], true) ? $reservation->billing_status : 'approved');
        $tab = ['archive' => 'Done', 'approved' => 'Approved', 'billed' => 'Billed', 'paid' => 'Paid'][$destination];
        $billingLabel = ucfirst($reservation->billing_status);
        $this->statusFilter = $destination;
        $message = 'SOA saved for "'.$reservation->title.'". Status: Approved · '.$billingLabel.'. View: '.$tab.'. ';
        $message .= $reservation->soa_locked
            ? 'Payment records retained.'
            : ($reservation->soa_sent_at ? 'Updated SOA not sent. Use Billing → Send updated SOA.' : 'SOA not sent. Use Billing → Send SOA to requester.');
        $this->dispatch('flash', type: $reservation->soa_locked ? 'success' : 'warning', message: $message);
    }

    public function selectBillingEmail(int $id, string $action = 'soa'): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        abort_unless(in_array($action, ['soa', 'reminder'], true), 422);
        $reservation = ResourceReservation::findOrFail($id);
        abort_if($reservation->soa_locked, 422, 'Billing emails cannot be sent after payment is recorded.');
        $this->billingEmailId = $id;
        $this->billingEmailAction = $action;
        $this->showBillingEmailModal = true;
    }

    public function getBillingEmailReservationProperty()
    {
        return $this->billingEmailId ? ResourceReservation::find($this->billingEmailId) : null;
    }

    public function cancelBillingEmail(): void
    {
        $this->showBillingEmailModal = false;
        $this->billingEmailId = null;
    }

    public function sendBillingEmail(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        if (!$this->billingEmailId) { return; }
        try {
            app(\App\Services\FacilityBillingEmailService::class)->queue($this->billingEmailId, $this->billingEmailAction);
            $this->cancelBillingEmail();
            $this->dispatch('flash', type: 'success', message: 'Billing email queued for the requester. Delivery status will update after it is sent.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('flash', type: 'error', message: $e instanceof \RuntimeException ? $e->getMessage() : 'The billing email could not be queued. Please try again.');
        }
    }

    public function selectForSoaRemoval(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::findOrFail($id);
        abort_unless($reservation->status === 'approved' && $reservation->soa_path, 422);
        abort_if($reservation->soa_locked, 422, 'The SOA cannot be changed after payment is recorded.');
        $this->removeSoaId = $id;
        $this->showRemoveSoaModal = true;
    }

    public function cancelSoaRemoval(): void
    {
        $this->removeSoaId = null;
        $this->showRemoveSoaModal = false;
    }

    public function confirmSoaRemoval(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        if ($this->removeSoaId === null) {
            return;
        }
        try {
            \DB::transaction(function () {
                $reservation = ResourceReservation::whereKey($this->removeSoaId)->lockForUpdate()->firstOrFail();
                if ($reservation->soa_locked) {
                    throw new \RuntimeException('The SOA cannot be changed after payment is recorded.');
                }
                if ($reservation->status !== 'approved' || !$reservation->soa_path) {
                    throw new \RuntimeException('This approved reservation has no SOA to remove.');
                }
                if (!\Storage::disk(config('filesystems.facility_upload_disk'))->delete($reservation->soa_path)) {
                    throw new \RuntimeException('SOA file could not be removed.');
                }
                $paymentProofPath = $reservation->payment_proof_path;
                $reservation->update([
                    'soa_path' => null, 'soa_sent_at' => null, 'payment_due_at' => null,
                    'billing_status' => 'unbilled', 'paid_at' => null, 'payment_proof_path' => null,
                    'soa_email_pending' => false,
                    'finished_confirmed_at' => null, 'finished_confirmed_by' => null,
                ]);
                if ($paymentProofPath) {
                    \Storage::disk(config('filesystems.facility_upload_disk'))->delete($paymentProofPath);
                }
            });
            $this->removeSoaId = null;
            $this->showRemoveSoaModal = false;
            $this->statusFilter = 'approved';
            $this->dispatch('flash', type: 'success', message: 'SOA removed. Status: Approved · Unbilled. View: Approved. The reservation can now be deleted.');
        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
    }

    public function selectForPayment(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::findOrFail($id);
        abort_unless($reservation->status === 'approved' && $reservation->soa_path, 422);
        $this->paymentId = $id;
        $this->paymentDate = $reservation->paid_at?->toDateString() ?? now()->toDateString();
        $this->paymentProof = null;
        $this->paymentProofs = [];
        $this->resetValidation(['paymentDate', 'paymentProof', 'paymentProofs']);
    }

    public function getPaymentReservationProperty()
    {
        return $this->paymentId ? ResourceReservation::with('paymentProofs')->find($this->paymentId) : null;
    }

    public function recordPayment(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::findOrFail($this->paymentId);
        abort_unless($reservation->status === 'approved' && $reservation->soa_path, 422);
        $this->validate([
            'paymentDate' => 'required|date|before_or_equal:today',
            'paymentProof' => (!$reservation->payment_proof_path && !$reservation->paymentProofs()->exists() && !$this->paymentProofs ? 'required' : 'nullable') . '|file|max:10240|mimes:pdf,jpg,jpeg,png',
            'paymentProofs' => 'array|max:10',
            'paymentProofs.*' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ], ['paymentProof.required' => 'Upload at least one payment proof.']);

        $paths = [];
        $paymentReceipt = null;
        $paymentStatusMessage = '';
        $savedReservation = null;
        try {
            $uploads = $this->paymentProofs;
            if ($this->paymentProof) { $uploads[] = $this->paymentProof; }
            foreach ($uploads as $upload) {
                $path = $upload->storeAs('reservations/payments', Str::uuid() . '.' . $upload->getClientOriginalExtension(), config('filesystems.facility_upload_disk'));
                if ($path) {
                    $paths[] = ['path' => $path, 'original_name' => $upload->getClientOriginalName()];
                }
                if (!$path || !\Storage::disk(config('filesystems.facility_upload_disk'))->exists($path)) {
                    throw new \RuntimeException('Payment proof upload failed.');
                }
            }
            \DB::transaction(function () use ($paths, &$paymentReceipt, &$savedReservation) {
                $reservation = ResourceReservation::whereKey($this->paymentId)->lockForUpdate()->firstOrFail();
                if ($reservation->status !== 'approved' || !$reservation->soa_path) {
                    throw new \RuntimeException('An approved reservation with an SOA is required.');
                }
                $firstPayment = !$reservation->soa_locked;
                if ($reservation->payment_proof_path && !$reservation->paymentProofs()->where('path', $reservation->payment_proof_path)->exists()) {
                    $reservation->paymentProofs()->create([
                        'path' => $reservation->payment_proof_path, 'original_name' => basename($reservation->payment_proof_path),
                        'paid_on' => $reservation->paid_at?->toDateString(),
                    ]);
                }
                foreach ($paths as $proof) {
                    $reservation->paymentProofs()->create($proof + ['paid_on' => $this->paymentDate, 'recorded_by' => auth()->id()]);
                }
                $reservation->update([
                    'billing_status' => 'paid',
                    'paid_at' => \Carbon\Carbon::parse($this->paymentDate)->startOfDay(),
                    'payment_proof_path' => $reservation->payment_proof_path ?: ($paths[0]['path'] ?? null),
                    'soa_email_pending' => false,
                ]);
                $savedReservation = $reservation;
                if ($firstPayment || $paths) {
                    $paymentReceipt = [
                        'recipient' => $reservation->requester_email,
                        'title' => $reservation->title, 'reservation_id' => $reservation->id,
                        'event_date' => $reservation->start_datetime->format('M j, Y g:i A').'–'.$reservation->end_datetime->format('M j, Y g:i A'),
                        'room' => $reservation->room_names, 'paid_date' => $reservation->paid_at->format('M j, Y'),
                    ];
                }
            });
            $this->statusFilter = $savedReservation->is_archived ? 'archive' : 'paid';
            $tab = $savedReservation->is_archived ? 'Done' : 'Paid';
            $paymentStatusMessage = 'Payment recorded for "'.$savedReservation->title.'". Status: Approved · Paid. View: '.$tab.'.';
            $this->modal('payment-reservation')->close();
        } catch (\Throwable $e) {
            foreach ($paths as $proof) {
                $this->cleanFailedUpload($proof['path']);
            }
            report($e);
            $this->addError('paymentProof', 'Payment could not be recorded. Please try again.');
            return;
        }
        if ($paymentReceipt) {
            try {
                if (!filter_var($paymentReceipt['recipient'], FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('A valid requester email address is required.');
                }
                Mail::to($paymentReceipt['recipient'])->queue(new \App\Mail\FacilityPaymentReceived($paymentReceipt));
                $this->dispatch('flash', type: 'success', message: $paymentStatusMessage.' Payment received email queued.');
            } catch (\Throwable $e) {
                report($e);
                $this->dispatch('flash', type: 'error', message: $paymentStatusMessage.' Confirmation email could not be queued.');
            }
        } else {
            $this->dispatch('flash', type: 'success', message: $paymentStatusMessage);
        }
    }

    public function render()
    {
        return view('livewire.resources.reservation-index');
    }
}
