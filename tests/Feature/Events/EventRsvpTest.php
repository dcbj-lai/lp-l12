<?php

namespace Tests\Feature\Events;

use App\Livewire\Events\EventList;
use App\Livewire\Events\AttendToggle;
use App\Livewire\EventsCard;
use App\Mail\EventRsvpAcknowledgment;
use App\Mail\EventRsvpReceived;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class EventRsvpTest extends TestCase
{
    use RefreshDatabase;

    protected function setHrEmail(string $email): void
    {
        putenv("EVENTS_HR_EMAIL={$email}");
        $_ENV['EVENTS_HR_EMAIL'] = $email;
        $_SERVER['EVENTS_HR_EMAIL'] = $email;
    }

    public function test_user_can_mark_attending_and_registration_is_saved(): void
    {
        Mail::fake();
        $event = Event::create(['title' => 'TB', 'status' => 'published']);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AttendToggle::class, ['event' => $event])
            ->call('respond', 'attending');

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'attending',
        ]);
    }

    public function test_user_custom_field_answers_are_saved_with_attending_rsvp(): void
    {
        Mail::fake();
        $event = Event::create([
            'title' => 'TB',
            'status' => 'published',
            'custom_field_labels' => ['Dietary requirements', 'T-shirt size', '', 'Accessibility needs'],
        ]);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AttendToggle::class, ['event' => $event])
            ->set('customFieldAnswers.0', 'Vegetarian')
            ->set('customFieldAnswers.1', 'Medium')
            ->set('customFieldAnswers.3', 'Aisle seat')
            ->call('respond', 'attending');

        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertSame(['Vegetarian', 'Medium', '', 'Aisle seat'], $registration->custom_field_answers);
    }

    public function test_custom_field_instructions_render_as_attendee_tooltip_text(): void
    {
        Mail::fake();
        $event = Event::create([
            'title' => 'TB',
            'status' => 'published',
            'custom_field_labels' => ['Dietary requirements', '', '', 'Accessibility needs'],
            'custom_field_instructions' => [
                'List any allergies or dietary restrictions.',
                '',
                '',
                'Add access needs or seating requests.',
            ],
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(AttendToggle::class, ['event' => $event])
            ->assertSee('Dietary requirements')
            ->assertSee('List any allergies or dietary restrictions.')
            ->assertSee('Accessibility needs')
            ->assertSee('Add access needs or seating requests.');
    }

    public function test_custom_fields_remain_visible_after_user_marks_not_attending(): void
    {
        Mail::fake();
        $event = Event::create([
            'title' => 'TB',
            'status' => 'published',
            'custom_field_labels' => ['Dietary requirements', 'T-shirt size', '', 'Accessibility needs'],
            'custom_field_instructions' => [
                'List any allergies or dietary restrictions.',
                'Enter your preferred shirt size.',
                '',
                'Add access needs or seating requests.',
            ],
        ]);
        $user = User::factory()->create();

        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'not_attending',
            'responded_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(AttendToggle::class, ['event' => $event])
            ->assertSee('Dietary requirements')
            ->assertSee('T-shirt size')
            ->assertSee('Accessibility needs')
            ->assertSee('Add access needs or seating requests.');
    }

    public function test_user_can_edit_emergency_and_health_details_from_attendee_view(): void
    {
        Mail::fake();
        $event = Event::create(['title' => 'TB', 'status' => 'published']);
        $user = User::factory()->create([
            'emergency_contact_name' => null,
            'emergency_contact_relationship' => null,
            'emergency_contact_phone' => null,
            'dietary_preference' => null,
            'medical_notes' => null,
        ]);

        Livewire::actingAs($user)
            ->test(AttendToggle::class, ['event' => $event])
            ->assertSee('Emergency &amp; Health', false)
            ->set('emergency_contact_name', 'Maria Santos')
            ->set('emergency_contact_relationship', 'Spouse')
            ->set('emergency_contact_phone', '+639171234567')
            ->set('dietary_preference', 'Vegetarian')
            ->set('medical_notes', 'Peanut allergy')
            ->call('saveProfileDetails')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Maria Santos', $user->emergency_contact_name);
        $this->assertSame('Spouse', $user->emergency_contact_relationship);
        $this->assertSame('+639171234567', $user->emergency_contact_phone);
        $this->assertSame('Vegetarian', $user->dietary_preference);
        $this->assertSame('Peanut allergy', $user->medical_notes);
    }

    public function test_emergency_and_health_details_are_saved_when_user_responds(): void
    {
        Mail::fake();
        $event = Event::create(['title' => 'TB', 'status' => 'published']);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AttendToggle::class, ['event' => $event])
            ->set('emergency_contact_name', 'Maria Santos')
            ->set('emergency_contact_relationship', 'Spouse')
            ->set('emergency_contact_phone', '+639171234567')
            ->set('dietary_preference', 'Halal')
            ->set('medical_notes', 'Asthma')
            ->call('respond', 'attending')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Maria Santos', $user->emergency_contact_name);
        $this->assertSame('Spouse', $user->emergency_contact_relationship);
        $this->assertSame('+639171234567', $user->emergency_contact_phone);
        $this->assertSame('Halal', $user->dietary_preference);
        $this->assertSame('Asthma', $user->medical_notes);
    }

    public function test_toggling_sends_acknowledgment_to_user(): void
    {
        Mail::fake();
        $event = Event::create(['title' => 'TB', 'status' => 'published']);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AttendToggle::class, ['event' => $event])
            ->call('respond', 'attending');

        Mail::assertQueued(EventRsvpAcknowledgment::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_toggling_notifies_hr_when_configured(): void
    {
        $this->setHrEmail('hr@example.com');
        Mail::fake();
        $event = Event::create(['title' => 'TB', 'status' => 'published']);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AttendToggle::class, ['event' => $event])
            ->call('respond', 'attending');

        Mail::assertQueued(EventRsvpReceived::class, fn ($mail) => $mail->hasTo('hr@example.com'));
    }

    public function test_user_can_change_response_to_not_attending(): void
    {
        Mail::fake();
        $event = Event::create(['title' => 'TB', 'status' => 'published']);
        $user = User::factory()->create();
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'attending']);

        Livewire::actingAs($user)
            ->test(AttendToggle::class, ['event' => $event])
            ->call('respond', 'not_attending');

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'not_attending',
        ]);
        $this->assertEquals(1, EventRegistration::where('event_id', $event->id)->count());
    }

    public function test_rsvp_is_blocked_after_deadline(): void
    {
        Mail::fake();
        $event = Event::create(['title' => 'TB', 'status' => 'published', 'rsvp_deadline' => now()->subDay()]);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AttendToggle::class, ['event' => $event])
            ->call('respond', 'attending');

        $this->assertDatabaseCount('event_registrations', 0);
        Mail::assertNothingQueued();
    }

    public function test_events_card_lists_published_upcoming_events(): void
    {
        Event::create(['title' => 'Future Fest', 'status' => 'published', 'start_datetime' => now()->addWeek()]);
        Event::create(['title' => 'Hidden Draft', 'status' => 'draft', 'start_datetime' => now()->addWeek()]);

        Livewire::actingAs(User::factory()->create())
            ->test(EventsCard::class)
            ->assertSee('Events')
            ->assertDontSee('Announcements')
            ->assertSee('Future Fest')
            ->assertDontSee('Hidden Draft');
    }

    public function test_event_list_shows_rsvp_and_signed_up_actions(): void
    {
        Event::create(['title' => 'Future Fest', 'status' => 'published', 'start_datetime' => now()->addWeek()]);

        Livewire::actingAs(User::factory()->create())
            ->test(EventList::class)
            ->assertSee('Future Fest')
            ->assertSee('RSVP')
            ->assertSee('Who else signed up');
    }
}
