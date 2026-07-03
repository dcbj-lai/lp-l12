<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityReservationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_facility_admin_can_crud_reservations_and_cleanup_google_calendar_event(): void
    {
        Mail::fake();

        $admin = $this->facilityUser('facility.admin');
        $room = Resource::create([
            'name' => 'Board Room',
            'type' => 'room',
            'location' => 'Main Building',
            'capacity' => 16,
            'created_by' => $admin->id,
        ]);
        $projector = Resource::create([
            'name' => 'Projector',
            'type' => 'equipment',
            'created_by' => $admin->id,
        ]);

        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson(route('facility-reservations.api.store'), [
                'requester_email' => 'requester@example.com',
                'resource_id' => $room->id,
                'equipment_ids' => [$projector->id],
                'title' => 'Strategy Meeting',
                'start_datetime' => '2026-07-06 09:00:00',
                'end_datetime' => '2026-07-06 10:00:00',
                'notes' => 'Needs projector.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Strategy Meeting')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.resource.id', $room->id)
            ->assertJsonPath('data.equipment.0.id', $projector->id);

        $reservationId = $createResponse->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->getJson(route('facility-reservations.api.index', ['search' => 'strategy']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $reservationId);

        $this->actingAs($admin, 'sanctum')
            ->patchJson(route('facility-reservations.api.update', $reservationId), [
                'title' => 'Updated Strategy Meeting',
                'equipment_ids' => [],
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Strategy Meeting')
            ->assertJsonPath('data.equipment', []);

        $reservation = ResourceReservation::findOrFail($reservationId);
        $reservation->update([
            'status' => 'approved',
            'google_event_id' => 'calendar-event-19',
        ]);

        $calendar = Mockery::mock(GoogleCalendarService::class);
        $calendar->shouldReceive('deleteEvent')
            ->once()
            ->with('calendar-event-19');
        $this->app->instance(GoogleCalendarService::class, $calendar);

        $this->actingAs($admin, 'sanctum')
            ->postJson(route('facility-reservations.api.cleanup-google-calendar', $reservationId))
            ->assertOk()
            ->assertJsonPath('cleaned', true)
            ->assertJsonPath('deleted_google_event_id', 'calendar-event-19')
            ->assertJsonPath('data.google_event_id', null);

        $this->assertNull($reservation->fresh()->google_event_id);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson(route('facility-reservations.api.destroy', $reservationId))
            ->assertNoContent();

        $this->assertDatabaseMissing('resource_reservations', [
            'id' => $reservationId,
        ]);
    }

    public function test_reject_api_deletes_google_event_before_rejecting_reservation(): void
    {
        Mail::fake();

        $approver = $this->facilityUser('facility.approver');
        $reservation = ResourceReservation::create([
            'requester_email' => 'requester@example.com',
            'title' => 'Facility Reservation',
            'start_datetime' => '2026-07-06 09:00:00',
            'end_datetime' => '2026-07-06 10:00:00',
            'status' => 'approved',
            'google_event_id' => 'calendar-event-19',
        ]);

        $calendar = Mockery::mock(GoogleCalendarService::class);
        $calendar->shouldReceive('deleteEvent')
            ->once()
            ->with('calendar-event-19');
        $this->app->instance(GoogleCalendarService::class, $calendar);

        $this->actingAs($approver, 'sanctum')
            ->postJson(route('facility-reservations.api.reject', $reservation), [
                'approval_note' => 'Not available.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.google_event_id', null);

        $reservation->refresh();

        $this->assertSame('rejected', $reservation->status);
        $this->assertNull($reservation->google_event_id);
    }

    public function test_reject_api_does_not_reject_when_google_calendar_cleanup_fails(): void
    {
        Mail::fake();

        $approver = $this->facilityUser('facility.approver');
        $reservation = ResourceReservation::create([
            'requester_email' => 'requester@example.com',
            'title' => 'Facility Reservation',
            'start_datetime' => '2026-07-06 09:00:00',
            'end_datetime' => '2026-07-06 10:00:00',
            'status' => 'approved',
            'google_event_id' => 'calendar-event-19',
        ]);

        $calendar = Mockery::mock(GoogleCalendarService::class);
        $calendar->shouldReceive('deleteEvent')
            ->once()
            ->with('calendar-event-19')
            ->andThrow(new \RuntimeException('Google Calendar unavailable.'));
        $this->app->instance(GoogleCalendarService::class, $calendar);

        $this->actingAs($approver, 'sanctum')
            ->postJson(route('facility-reservations.api.reject', $reservation), [
                'approval_note' => 'Not available.',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Unable to delete the Google Calendar event. Reservation was not rejected.')
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.google_event_id', 'calendar-event-19');

        $reservation->refresh();

        $this->assertSame('approved', $reservation->status);
        $this->assertSame('calendar-event-19', $reservation->google_event_id);
    }

    protected function facilityUser(string $role): User
    {
        $role = Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
