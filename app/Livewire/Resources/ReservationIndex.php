<?php

namespace App\Livewire\Resources;

use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Services\ResourceReservationService;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Resource Reservations')]
class ReservationIndex extends Component
{
    use WithFileUploads;

    public string $statusFilter = 'pending';
    public string $eventDateSort = 'asc';
    public string $requestDateSort = 'desc';
    public ?string $selectedSeriesId = null;
    public ?int $reservationId = null;
    public ?string $approvalNote = null;
    public ?int $deleteId = null;
    public bool $showDeleteModal = false;
    public ?int $editId = null;
    public string $editTitle = '';
    public ?int $editRoomId = null;
    public string $editStart = '';
    public string $editEnd = '';
    public ?int $editPax = null;
    public string $editSetup = '';
    public string $editContact = '';
    public string $editNotes = '';
    public string $editConsumables = '';
    public string $editBillingStatus = 'unbilled';
    public array $editEquipment = [];
    public ?int $editEquipmentToAdd = null;
    public $editFloorPlan;
    public $editGatePass;
    public ?int $billingId = null;
    public ?int $removeSoaId = null;
    public bool $showRemoveSoaModal = false;
    public $soaFile;
    public string $soaSentDate = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['facility.admin', 'facility.approver']), 403);
    }

    public function getReservationsProperty()
    {
        $byRequestDate = $this->statusFilter === 'pending';
        $direction = ($byRequestDate ? $this->requestDateSort : $this->eventDateSort) === 'desc' ? 'desc' : 'asc';
        $dateColumn = $byRequestDate ? 'created_at' : 'start_datetime';

        return ResourceReservation::with(['resource', 'equipment', 'items.resource'])
            ->when($this->statusFilter === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when(!in_array($this->statusFilter, ['all', 'deleted']), fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy($dateColumn, $direction)->orderBy('id', $direction)->get()
            ->groupBy(fn ($reservation) => $reservation->{$dateColumn}->format('F Y'));
    }

    public function getBookingGroupsProperty()
    {
        $byRequestDate = $this->statusFilter === 'pending';
        $reservations = $this->reservations->flatten(1);

        $groups = $reservations
            ->groupBy(fn ($reservation) => $reservation->recurrence_series_id ?: 'single-' . $reservation->id)
            ->map(function ($occurrences, $key) use ($byRequestDate) {
                $ordered = $occurrences->sortBy('start_datetime')->values();
                $upcoming = $ordered->first(fn ($reservation) => $reservation->start_datetime->gte(now()));
                $representative = $upcoming ?? $ordered->last();
                $requestDate = $occurrences->max('created_at');
                $conflicts = $occurrences->mapWithKeys(fn ($reservation) => [$reservation->id => $this->conflictsFor($reservation)])
                    ->filter(fn ($names) => $names);

                return [
                    'key' => $key,
                    'is_series' => (bool) $representative->recurrence_series_id,
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
        return $groups->sort(function ($a, $b) use ($byRequestDate, $direction) {
            if (!$byRequestDate && $a['has_upcoming'] !== $b['has_upcoming']) {
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

    public function getSelectedSeriesReservationsProperty()
    {
        if (!$this->selectedSeriesId) {
            return collect();
        }

        return ResourceReservation::withTrashed()->with(['resource', 'equipment'])
            ->where('recurrence_series_id', $this->selectedSeriesId)
            ->orderBy('start_datetime')->get();
    }

    public function showRecurringSeries(string $seriesId): void
    {
        $this->selectedSeriesId = $seriesId;
        $this->modal('recurring-series')->show();
    }

    public function getRoomsProperty()
    {
        return Resource::where('type', 'room')->where('capacity', '>', 0)->orderBy('name')->get();
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
        if ($reservation->status === 'approved' && $reservation->soa_path) {
            $this->dispatch('flash', type: 'error', message: 'Remove the SOA before deleting this approved reservation.');
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
            if ($reservation->status === 'approved' && $reservation->soa_path) {
                throw new \RuntimeException('Remove the SOA before deleting this approved reservation.');
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

    public function selectForEdit(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::with('equipment')->findOrFail($id);
        $this->editId = $id;
        $this->editTitle = $reservation->title;
        $this->editRoomId = $reservation->resource_id;
        $this->editStart = $reservation->start_datetime->format('Y-m-d\TH:i');
        $this->editEnd = $reservation->end_datetime->format('Y-m-d\TH:i');
        $this->editPax = $reservation->number_of_pax;
        $this->editSetup = $reservation->setup_arrangement ?? '';
        $this->editContact = $reservation->contact_number ?? '';
        $this->editNotes = $reservation->notes ?? '';
        $this->editConsumables = $reservation->consumables ?? '';
        $this->editBillingStatus = $reservation->billing_status;
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
            'editTitle' => 'required|string|max:255', 'editRoomId' => 'required|exists:resources,id',
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
            $canEditBilling = in_array($reservation->billing_status, ['billed', 'paid'], true);
            if ($canEditBilling) {
                $this->validate(['editBillingStatus' => 'required|in:billed,paid']);
            }
            foreach (['editFloorPlan' => 'floor_plan_path', 'editGatePass' => 'gate_pass_path'] as $property => $column) {
                if ($this->{$property}) {
                    $paths[$column] = $this->{$property}->storeAs('reservations', Str::uuid() . '.' . $this->{$property}->getClientOriginalExtension(), config('filesystems.facility_upload_disk'));
                }
            }
            $reservation = app(ResourceReservationService::class)->update($reservation, [
                'title' => $this->editTitle, 'resource_id' => $this->editRoomId,
                'start_datetime' => $this->editStart, 'end_datetime' => $this->editEnd,
                'number_of_pax' => $this->editPax, 'setup_arrangement' => $this->editSetup,
                'contact_number' => $this->editContact, 'notes' => $this->editNotes,
                'consumables' => $this->editConsumables,
                'equipment_quantities' => array_filter($this->editEquipment, fn ($quantity) => (int) $quantity > 0),
            ] + $paths);
            if ($canEditBilling && $this->editBillingStatus !== $reservation->billing_status) {
                $reservation->update([
                    'billing_status' => $this->editBillingStatus,
                    'paid_at' => $this->editBillingStatus === 'paid' ? now() : null,
                ]);
            }
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
        $this->resetValidation(['soaFile', 'soaSentDate']);
        $reservation = ResourceReservation::findOrFail($id);
        $this->billingId = $id;
        $this->soaSentDate = $reservation->soa_sent_at?->toDateString() ?? now()->toDateString();
        $this->soaFile = null;
    }

    public function updatedSoaFile(): void
    {
        $this->resetValidation('soaFile');
    }

    public function markBilled(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $this->validate(['soaFile' => 'required|file|max:10240|mimes:pdf,doc,docx', 'soaSentDate' => 'required|date|before_or_equal:today']);
        $reservation = ResourceReservation::findOrFail($this->billingId);
        abort_unless($reservation->status === 'approved', 422);
        $oldPath = $reservation->soa_path;
        $wasPaid = $reservation->billing_status === 'paid';
        $path = null;
        try {
            $path = $this->soaFile->storeAs('reservations/soa', Str::uuid() . '.' . $this->soaFile->getClientOriginalExtension(), config('filesystems.facility_upload_disk'));
            if (!$path) {
                throw new \RuntimeException('SOA upload failed. Please try again.');
            }
            $sent = \Carbon\Carbon::parse($this->soaSentDate);
            $reservation->update([
                'soa_path' => $path, 'soa_sent_at' => $sent, 'payment_due_at' => $sent->copy()->addDays(15),
                'billing_status' => $wasPaid ? 'paid' : 'billed',
                'paid_at' => $wasPaid ? $reservation->paid_at : null,
            ]);
        } catch (\Throwable $e) {
            if ($path) {
                \Storage::disk(config('filesystems.facility_upload_disk'))->delete($path);
            }
            report($e);
            $this->addError('soaFile', 'SOA upload failed. Please try again.');
            return;
        }
        if ($oldPath && $oldPath !== $path && !\Storage::disk(config('filesystems.facility_upload_disk'))->delete($oldPath)) {
            report(new \RuntimeException('Unable to remove replaced SOA file: ' . $oldPath));
        }
        $this->modal('billing-reservation')->close();
        $this->dispatch('flash', type: 'success', message: $wasPaid ? 'SOA replaced. Payment remains recorded as paid.' : 'SOA recorded. Payment is due in 15 days.');
    }

    public function selectForSoaRemoval(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::findOrFail($id);
        abort_unless($reservation->status === 'approved' && $reservation->soa_path, 422);
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
            $reservation = ResourceReservation::findOrFail($this->removeSoaId);
            if ($reservation->status !== 'approved' || !$reservation->soa_path) {
                throw new \RuntimeException('This approved reservation has no SOA to remove.');
            }
            if (!\Storage::disk(config('filesystems.facility_upload_disk'))->delete($reservation->soa_path)) {
                throw new \RuntimeException('SOA file could not be removed.');
            }
            $reservation->update([
                'soa_path' => null, 'soa_sent_at' => null, 'payment_due_at' => null,
                'billing_status' => 'unbilled', 'paid_at' => null,
            ]);
            $this->removeSoaId = null;
            $this->showRemoveSoaModal = false;
            $this->dispatch('flash', type: 'success', message: 'SOA removed. The reservation can now be deleted.');
        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
    }

    public function markPaid(int $id): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $reservation = ResourceReservation::findOrFail($id);
        abort_unless($reservation->billing_status === 'billed', 422);
        $reservation->update(['billing_status' => 'paid', 'paid_at' => now()]);
        $this->dispatch('flash', type: 'success', message: 'Payment recorded.');
    }

    public function render()
    {
        return view('livewire.resources.reservation-index');
    }
}
