<?php

namespace App\Livewire\Resources;

use App\Models\ResourceReservation;
use App\Services\ResourceReservationService;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Resource Reservations')]
class ReservationIndex extends Component
{
    public $reservations = [];
    public ?int $reservationId = null;
    public string $action = 'approve';
    public ?string $approvalNote = null;

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

    public function approve(int $id): void
    {
        try {
            $reservation = ResourceReservation::findOrFail($id);

            app(ResourceReservationService::class)
                ->approveReservation($reservation, auth()->id());

            $this->dispatch(
                'flash',
                type: 'success',
                message: 'Reservation approved.'
            );

        } catch (\Throwable $e) {
            Log::warning('Approval failed', [
                'reservation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch(
                'flash',
                type: 'error',
                message: $e->getMessage()
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

    public function delete($id)
    {
        try {
            $reservation = ResourceReservation::findOrFail($id);

            // 🔥 Delete calendar event if exists
            if ($reservation->google_event_id) {
                try {
                    app(\App\Services\GoogleCalendarService::class)
                        ->deleteEvent($reservation->google_event_id);
                } catch (\Throwable $e) {
                    \Log::warning('Failed to delete event on reservation delete', [
                        'reservation_id' => $id,
                        'event_id' => $reservation->google_event_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 🔥 Delete attachment from S3
            if ($reservation->attachment_path) {
                try {
                    \Storage::disk('s3')->delete($reservation->attachment_path);
                } catch (\Throwable $e) {
                    \Log::warning('Failed to delete attachment from S3', [
                        'reservation_id' => $id,
                        'path' => $reservation->attachment_path,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 🔥 Delete reservation
            $reservation->delete();

            $this->dispatch(
                'flash',
                type: 'warning',
                message: 'Reservation deleted.'
            );

            $this->loadReservations();

        } catch (\Throwable $e) {
            \Log::error('Delete reservation failed', [
                'reservation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch(
                'flash',
                type: 'error',
                message: 'Failed to delete reservation.'
            );
        }
    }

    public function confirmApprove(): void
    {
        try {
            $reservation = ResourceReservation::findOrFail($this->reservationId);

            app(ResourceReservationService::class)
                ->approveReservation(
                    $reservation,
                    auth()->id(),
                    $this->approvalNote
                );

            $this->dispatch('flash', type: 'success', message: 'Reservation approved.');
            $this->loadReservations();
            $this->reset(['reservationId', 'approvalNote']);

        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
        $this->modal('approve-reservation')->close();
    }

    public function confirmReject(): void
    {
        try {
            if (blank($this->approvalNote)) {
                throw new \Exception('Rejection reason is required.');
            }

            $reservation = ResourceReservation::findOrFail($this->reservationId);

            app(ResourceReservationService::class)
                ->rejectReservation(
                    $reservation,
                    auth()->id(),
                    $this->approvalNote
                );

            $this->loadReservations();
            $this->dispatch('flash', type: 'info', message: 'Reservation rejected.');
            $this->reset(['reservationId', 'approvalNote']);

        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
        $this->modal('reject-reservation')->close();
    }


    public function render()
    {
        return view('livewire.resources.reservation-index');
    }
}
