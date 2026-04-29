<?php

namespace App\Livewire\Resources;

use Livewire\Component;
use App\Models\Resource;
use App\Services\ResourceReservationService;
use Livewire\Attributes\Validate;

class CreateReservation extends Component
{
    public $rooms = [];
    public $equipment = [];

    #[Validate('required|email')]
    public $requester_email = '';

    public $resource_id = null;
    public $equipment_ids = [];

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('required|date')]
    public $start_datetime;

    #[Validate('required|date|after:start_datetime')]
    public $end_datetime;

    public $selected_equipment_to_add = null;

    #[Validate('nullable|string|max:500')]
    public $notes = '';

    public function mount()
    {
        $this->rooms = Resource::where('type', 'room')->orderBy('name')->get();
        $this->equipment = Resource::where('type', 'equipment')->orderBy('name')->get();
    }

    public function submitReservation(ResourceReservationService $service)
    {
        $this->validate();

        // dd($this->requester_email, $this->resource_id, $this->equipment_ids, $this->title, $this->start_datetime, $this->end_datetime, $this->notes);

        try {
            $service->create([
                'user_id' => null, // 🔥 public booking
                'requester_email' => $this->requester_email,
                'resource_id' => $this->resource_id,
                'equipment_ids' => $this->equipment_ids,
                'title' => $this->title,
                'start_datetime' => $this->start_datetime,
                'end_datetime' => $this->end_datetime,
                'notes' => $this->notes,
            ]);

            $this->dispatch('flash', type: 'success', message: 'Your booking request has been submitted for approval.');

            $this->reset([
                'requester_email',
                'resource_id',
                'equipment_ids',
                'title',
                'start_datetime',
                'end_datetime',
                'notes',
            ]);

        } catch (\Throwable $e) {
            \Log::error('Booking failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch(
                'flash',
                type: 'error',
                message: $e->getMessage() ?: 'An error occurred while submitting your booking request. Please try again later.'
            );
        }
    }

    public function addEquipment()
    {
        if (!$this->selected_equipment_to_add) {
            return;
        }

        if (!in_array($this->selected_equipment_to_add, $this->equipment_ids)) {
            $this->equipment_ids[] = (int) $this->selected_equipment_to_add;
        }

        $this->selected_equipment_to_add = null;
    }

    public function removeEquipment($id)
    {
        $this->equipment_ids = array_values(
            array_filter($this->equipment_ids, fn($item) => $item != $id)
        );
    }

    public function render()
    {
        return view('livewire.resources.create-reservation');
    }
}
