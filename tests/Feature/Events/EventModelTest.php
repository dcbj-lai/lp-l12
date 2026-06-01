<?php

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_helper_reflects_status(): void
    {
        $this->assertTrue((new Event(['status' => 'published']))->isPublished());
        $this->assertFalse((new Event(['status' => 'draft']))->isPublished());
    }

    public function test_rsvp_closed_helper(): void
    {
        $this->assertFalse((new Event(['rsvp_deadline' => null]))->rsvpClosed());
        $this->assertFalse((new Event(['rsvp_deadline' => now()->addDay()]))->rsvpClosed());
        $this->assertTrue((new Event(['rsvp_deadline' => now()->subDay()]))->rsvpClosed());
    }

    public function test_attending_registrations_relation_excludes_decliners(): void
    {
        $event = Event::create(['title' => 'TB', 'status' => 'published']);
        $going = User::factory()->create();
        $notGoing = User::factory()->create();

        EventRegistration::create(['event_id' => $event->id, 'user_id' => $going->id, 'status' => 'attending']);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $notGoing->id, 'status' => 'not_attending']);

        $this->assertEquals(2, $event->registrations()->count());
        $this->assertEquals(1, $event->attendingRegistrations()->count());
    }

    public function test_deleting_event_cascades_registrations(): void
    {
        $event = Event::create(['title' => 'TB', 'status' => 'published']);
        $user = User::factory()->create();
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'attending']);

        $event->delete();

        $this->assertDatabaseCount('event_registrations', 0);
    }
}
