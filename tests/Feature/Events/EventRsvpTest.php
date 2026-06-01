<?php

namespace Tests\Feature\Events;

use App\Livewire\AnnouncementsCard;
use App\Livewire\EventRegistrantsCard;
use App\Livewire\Events\AttendToggle;
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

    public function test_announcements_card_lists_published_upcoming_events(): void
    {
        Event::create(['title' => 'Future Fest', 'status' => 'published', 'start_datetime' => now()->addWeek()]);
        Event::create(['title' => 'Hidden Draft', 'status' => 'draft', 'start_datetime' => now()->addWeek()]);

        Livewire::actingAs(User::factory()->create())
            ->test(AnnouncementsCard::class)
            ->assertSee('Future Fest')
            ->assertDontSee('Hidden Draft');
    }

    public function test_registrants_card_shows_attendees(): void
    {
        $event = Event::create(['title' => 'TB', 'status' => 'published', 'start_datetime' => now()->addWeek()]);
        $user = User::factory()->create(['name' => 'Jane Attendee']);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'attending', 'responded_at' => now()]);

        Livewire::actingAs(User::factory()->create())
            ->test(EventRegistrantsCard::class)
            ->assertSee('Jane Attendee');
    }
}
