<?php

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('events.manage', 'web');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/events')->assertRedirect('/login');
    }

    public function test_any_authenticated_user_can_view_events_list(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/events')->assertOk();
    }

    public function test_shared_event_detail_is_visible_to_any_authenticated_user(): void
    {
        $event = Event::create(['title' => 'Team Building', 'status' => 'published']);

        $this->actingAs(User::factory()->create()); // plain user, no roles/permissions
        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Team Building');
    }

    public function test_signed_up_page_is_visible_to_any_authenticated_user_and_only_shows_attendees(): void
    {
        $event = Event::create(['title' => 'Team Building', 'status' => 'published']);
        $attending = User::factory()->create(['name' => 'Jane Attendee']);
        $declined = User::factory()->create(['name' => 'No Person']);

        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $attending->id,
            'status' => 'attending',
            'responded_at' => now(),
        ]);
        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $declined->id,
            'status' => 'not_attending',
            'responded_at' => now(),
        ]);

        $this->actingAs(User::factory()->create());
        $this->get(route('events.registrants', $event))
            ->assertOk()
            ->assertSee('Who else signed up')
            ->assertSee('Jane Attendee')
            ->assertDontSee('No Person');
    }

    public function test_manage_page_is_forbidden_without_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/manage/events')->assertForbidden();
    }

    public function test_manage_page_is_accessible_with_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('events.manage');

        $this->actingAs($user);
        $this->get('/manage/events')->assertOk();
    }
}
