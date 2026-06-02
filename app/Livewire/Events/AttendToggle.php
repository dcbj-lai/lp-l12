<?php

namespace App\Livewire\Events;

use App\Mail\EventRsvpAcknowledgment;
use App\Mail\EventRsvpReceived;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AttendToggle extends Component
{
    public Event $event;
    public ?string $status = null; // attending | not_attending | null (no response yet)
    public int $guestCount = 0;
    public array $customFieldAnswers = ['', '', '', ''];
    public string $emergency_contact_name = '';
    public string $emergency_contact_relationship = '';
    public string $emergency_contact_phone = '';
    public string $dietary_preference = '';
    public string $medical_notes = '';
    public array $relationshipOptions = ['Father', 'Mother', 'Sister', 'Brother', 'Spouse', 'Others'];

    public function mount(Event $event)
    {
        $this->event = $event;
        $this->hydrateProfileFields();

        $registration = $this->currentRegistration();
        if ($registration) {
            $this->status = $registration->status;
            $this->guestCount = $registration->guest_count;
            $this->customFieldAnswers = $registration->customFieldAnswers();
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

        $this->validate([
            'guestCount' => 'integer|min:0|max:99',
            'customFieldAnswers' => 'array',
            'customFieldAnswers.*' => 'nullable|string|max:255',
        ]);

        $this->persistProfileDetails(false);

        $answers = $status === 'attending'
            ? EventRegistration::normalizeCustomFieldAnswers($this->customFieldAnswers)
            : EventRegistration::normalizeCustomFieldAnswers([]);

        $registration = EventRegistration::updateOrCreate(
            ['event_id' => $this->event->id, 'user_id' => Auth::id()],
            [
                'status' => $status,
                'guest_count' => $status === 'attending' ? max(0, $this->guestCount) : 0,
                'custom_field_answers' => $answers,
                'responded_at' => now(),
            ]
        );

        $this->status = $registration->status;
        $this->guestCount = $registration->guest_count;
        $this->customFieldAnswers = $registration->customFieldAnswers();

        $this->sendNotifications($registration);

        $this->dispatch('flash', type: 'success', message: $status === 'attending'
            ? 'You are marked as attending.'
            : 'You are marked as not attending.');

        // Let dashboard / registrant cards refresh
        $this->dispatch('rsvp-updated');
    }

    public function saveProfileDetails(): void
    {
        $this->persistProfileDetails();
    }

    protected function hydrateProfileFields(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $this->emergency_contact_name = $user->emergency_contact_name ?? '';
        $this->emergency_contact_relationship = $user->emergency_contact_relationship ?? '';
        $this->emergency_contact_phone = $user->emergency_contact_phone ?? '';
        $this->dietary_preference = $user->dietary_preference ?? '';
        $this->medical_notes = $user->medical_notes ?? '';
    }

    protected function persistProfileDetails(bool $dispatchFlash = true): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $validated = $this->validate($this->profileRules());

        $user->update($validated);
        $this->hydrateProfileFields();

        if ($dispatchFlash) {
            $this->dispatch('flash', type: 'success', message: 'Emergency and health details updated.');
        }
    }

    protected function profileRules(): array
    {
        return [
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', Rule::in($this->relationshipOptions)],
            'emergency_contact_phone' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'dietary_preference' => ['nullable', 'string', 'max:255'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
        ];
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
