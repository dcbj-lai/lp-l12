<?php

namespace App\Livewire\Resources;

use App\Models\ResourceReservation;
use App\Services\FacilitySoaGenerator;
use App\Support\FacilityStaffRates;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class CreateSoa extends Component
{
    #[Locked]
    public ?int $reservationId = null;
    public string $client_name = '';
    public string $business_address = '';
    public string $event_type = '';
    public string $replacement_reason = '';
    public array $quantities = [];
    public array $hours = [];
    public array $extra_hours = [];
    public array $rate_to_add = [];
    public bool $editing = false;

    #[On('create-soa')]
    public function open(int $reservationId): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $booking = ResourceReservation::findOrFail($reservationId);
        abort_unless($booking->status === 'approved' && !$booking->soa_locked, 422);
        $this->resetValidation();
        $this->reset('event_type', 'business_address', 'replacement_reason');
        $this->reservationId = $reservationId;
        $this->client_name = $booking->requester_name ?? '';
        $this->quantities = array_fill_keys(array_keys(FacilitySoaGenerator::LABELS), 0);
        $duration = round(($booking->end_datetime->timestamp - $booking->start_datetime->timestamp) / 3600, 2);
        $this->hours = array_fill_keys(FacilitySoaGenerator::STAFF, $duration);
        $this->extra_hours = array_fill_keys(FacilitySoaGenerator::STAFF, []);
        $this->rate_to_add = array_fill_keys(FacilitySoaGenerator::STAFF, '');
        $document = \App\Support\FacilitySoaFilename::generatedDocument($booking);
        $this->editing = $document !== null;
        if ($document) {
            $snapshot = $document->snapshot;
            $this->client_name = $snapshot['client_name'];
            $this->business_address = $snapshot['business_address'] ?? '';
            $this->event_type = $snapshot['event_type'];
            foreach ($snapshot['lines'] as $line) {
                if (isset($line['staff_code'], $line['period'])) {
                    $this->quantities[$line['staff_code']] = $line['quantity'];
                    $this->extra_hours[$line['staff_code']][$line['period']] = $line['hours'];
                    continue;
                }
                $code = array_search($line['label'], FacilitySoaGenerator::LABELS, true);
                if ($code === false) continue;
                $this->quantities[$code] = $line['quantity'];
                if (in_array($code, FacilitySoaGenerator::STAFF, true)) $this->hours[$code] = $line['hours'] ?? $duration;
            }
            foreach (FacilitySoaGenerator::STAFF as $staff) {
                if (!collect($snapshot['lines'])->contains('label', FacilitySoaGenerator::LABELS[$staff])) $this->hours[$staff] = 0;
            }
        }
        $this->modal('create-facility-soa')->show();
    }

    private function data(): array
    {
        return ['client_name' => $this->client_name, 'business_address' => $this->business_address, 'event_type' => $this->event_type,
            'replacement_reason' => $this->replacement_reason, 'quantities' => $this->quantities, 'hours' => $this->hours, 'extra_hours' => $this->extra_hours];
    }

    public function addRate(string $staff): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $period = $this->rate_to_add[$staff] ?? '';
        abort_unless(in_array($staff, FacilitySoaGenerator::STAFF, true) && isset(FacilityStaffRates::labels()[$staff.'_'.$period]), 422);
        $this->extra_hours[$staff][$period] ??= 0;
        $this->rate_to_add[$staff] = '';
    }

    public function removeRate(string $staff, string $period): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        unset($this->extra_hours[$staff][$period]);
        $this->resetValidation();
    }

    public function getBookingProperty()
    {
        return $this->reservationId ? ResourceReservation::with(['rooms', 'resource'])->find($this->reservationId) : null;
    }

    public function getQuoteProperty(): array
    {
        if (!$this->booking || !$this->event_type) return [];
        try { return app(FacilitySoaGenerator::class)->quote($this->booking, $this->data()); }
        catch (\Illuminate\Validation\ValidationException $e) { return ['errors' => $e->validator->errors()->all()]; }
    }

    private function validateForm(): void
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);
        $this->validate(['client_name' => 'required|string|max:255', 'business_address' => 'nullable|string|max:1000',
            'event_type' => 'required|in:ministry,non_ministry', 'replacement_reason' => ($this->booking?->soa_path ? 'required' : 'nullable').'|string|max:2000']);
    }

    public function preview()
    {
        $this->validateForm();
        abort_unless($this->booking?->status === 'approved' && !$this->booking?->soa_locked, 422);
        $service = app(FacilitySoaGenerator::class);
        $bytes = $service->pdf($service->snapshot($this->booking, $this->data()), today());
        return response()->streamDownload(fn () => print($bytes), \App\Support\FacilitySoaFilename::forReservation($this->booking), ['Content-Type' => 'application/pdf']);
    }

    public function save(): void
    {
        $this->validateForm();
        try { app(FacilitySoaGenerator::class)->create($this->reservationId, $this->data()); }
        catch (\Illuminate\Validation\ValidationException $e) { throw $e; }
        catch (\Throwable $e) { report($e); $this->addError('client_name', 'SOA could not be generated. Please try again.'); return; }
        $this->modal('create-facility-soa')->close();
        $this->dispatch('soa-generated', reservationId: $this->reservationId, edited: $this->editing);
    }

    public function render()
    {
        return view('livewire.resources.create-soa', ['labels' => FacilitySoaGenerator::LABELS,
            'referenceRates' => $this->reservationId ? DB::table('facility_charge_rates')->pluck('amount_cents', 'code') : collect(),
            'ratePeriods' => FacilityStaffRates::PERIODS,
            'dueDate' => app(FacilitySoaGenerator::class)->dueDate(today())]);
    }
}
