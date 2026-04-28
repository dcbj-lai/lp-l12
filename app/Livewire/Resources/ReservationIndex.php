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

            // 🔥 Only block if already approved
            if ($reservation->status === 'approved') {
                throw new \Exception('Reservation is already approved.');
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
        try {
            $reservation = ResourceReservation::findOrFail($id);

            // 🔥 If previously approved → delete calendar event
            if ($reservation->google_event_id) {
                try {
                    app(\App\Services\GoogleCalendarService::class)
                        ->deleteEvent($reservation->google_event_id);
                } catch (\Throwable $e) {
                    \Log::warning('Failed to delete calendar event on reject', [
                        'reservation_id' => $reservation->id,
                        'event_id' => $reservation->google_event_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 🔥 Update reservation
            $reservation->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'google_event_id' => null, // ✅ prevent stale reference
            ]);

            $this->dispatch(
                'flash',
                type: 'warning',
                message: 'Reservation rejected.'
            );

            $this->loadReservations();

        } catch (\Throwable $e) {
            \Log::error('Reject failed', [
                'reservation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch(
                'flash',
                type: 'error',
                message: 'Failed to reject reservation.'
            );
        }
    }

    public function revoke($id)
    {
        try {
            $reservation = ResourceReservation::findOrFail($id);

            // 🔥 If there is a calendar event → delete it
            if ($reservation->google_event_id) {
                try {
                    app(\App\Services\GoogleCalendarService::class)
                        ->deleteEvent($reservation->google_event_id);
                } catch (\Throwable $e) {
                    \Log::warning('Failed to delete calendar event on revoke', [
                        'reservation_id' => $reservation->id,
                        'event_id' => $reservation->google_event_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 🔥 Reset to pending (clean state)
            $reservation->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'google_event_id' => null,
            ]);

            $this->dispatch(
                'flash',
                type: 'info',
                message: 'Approval revoked. Reservation is now pending.'
            );

            $this->loadReservations();

        } catch (\Throwable $e) {
            \Log::error('Revoke failed', [
                'reservation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch(
                'flash',
                type: 'error',
                message: 'Failed to revoke approval.'
            );
        }
    }

    public function render()
    {
        return view('livewire.resources.reservation-index');
    }
}
