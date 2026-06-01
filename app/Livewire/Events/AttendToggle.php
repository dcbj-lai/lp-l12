<?php

namespace App\Livewire\Events;

use App\Mail\EventRsvpAcknowledgment;
use App\Mail\EventRsvpReceived;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class AttendToggle extends Component
{
    public Event $event;
    public ?string $status = null; // attending | not_attending | null (no response yet)
    public int $guestCount = 0;

    public function mount(Event $event)
    {
        $this->event = $event;

        $registration = $this->currentRegistration();
        if ($registration) {
            $this->status = $registration->status;
            $this->guestCount = $registration->guest_count;
        }
    }

    protected function currentRegistration(): ?EventRegistration
    {
        return EventRegistration::where('event_id', $this->event->id)
            ->where('user_id', Auth::id())
            ->first();
    }

    public function respond(string $status)
    {
        if (!in_array($status, ['attending', 'not_attending'], true)) {
            return;
        }

        if ($this->event->rsvpClosed()) {
            $this->dispatch('flash', type: 'error', message: 'RSVP for this event is closed.');
            return;
        }

        $registration = EventRegistration::updateOrCreate(
            ['event_id' => $this->event->id, 'user_id' => Auth::id()],
            [
                'status' => $status,
                'guest_count' => $status === 'attending' ? max(0, $this->guestCount) : 0,
                'responded_at' => now(),
            ]
        );

        $this->status = $registration->status;

        $this->sendNotifications($registration);

        $this->dispatch('flash', type: 'success', message: $status === 'attending'
            ? 'You are marked as attending.'
            : 'You are marked as not attending.');

        // Let dashboard / registrant cards refresh
        $this->dispatch('rsvp-updated');
    }

    protected function sendNotifications(EventRegistration $registration): void
    {
        $registration->loadMissing(['event', 'user.department']);

        $hrEmail = env('EVENTS_HR_EMAIL', env('REQUESTS_HR_EMAIL'));

        // Notify PNC / HR
        if ($hrEmail) {
            Mail::to($hrEmail)->queue(new EventRsvpReceived($registration));
        }

        // Acknowledge the staff member
        if ($registration->user?->email) {
            Mail::to($registration->user->email)->queue(new EventRsvpAcknowledgment($registration));
        }
    }

    public function render()
    {
        return view('livewire.events.attend-toggle');
    }
}
