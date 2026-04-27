<?php

namespace App\Livewire\Resources;

use Livewire\Component;
use App\Models\ResourceReservation;
use Livewire\Attributes\Title;

#[Title('Resource Reservations')]
class ReservationIndex extends Component
{
    public $reservations = [];

    public function mount()
    {
        abort_unless(
            auth()->user()->hasAnyRole(['facility.admin', 'facility.approver']),
            403
        );

        $this->loadReservations();
    }

    public function loadReservations()
    {
        $this->reservations = ResourceReservation::with(['resource', 'equipment'])
            ->latest()
            ->get();
    }

    public function approve($id)
    {
        try {
            $reservation = ResourceReservation::with(['resource', 'equipment'])
                ->findOrFail($id);

            // 🔥 Prevent double approval
            if ($reservation->status !== 'pending') {
                throw new \Exception('Only pending reservations can be approved.');
            }

            // 🔥 Create Google Calendar event
            $googleEventId = app(\App\Services\GoogleCalendarService::class)
                ->createEvent($reservation);

            // 🔥 Update reservation
            $reservation->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'google_event_id' => $googleEventId,
            ]);

            $this->dispatch(
                'flash',
                type: 'success',
                message: 'Reservation approved and added to calendar.'
            );

            $this->loadReservations();

        } catch (\Throwable $e) {
            \Log::error('Approval failed', [
                'reservation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch(
                'flash',
                type: 'error',
                message: 'Failed to approve reservation.'
            );
        }
    }

    public function reject($id)
    {
        $reservation = ResourceReservation::findOrFail($id);

        $reservation->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $this->dispatch('flash', type: 'warning', message: 'Reservation rejected.');

        $this->loadReservations();
    }

    public function render()
    {
        return view('livewire.resources.reservation-index');
    }
}
