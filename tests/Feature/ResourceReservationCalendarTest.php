<?php

namespace Tests\Feature;

use App\Models\ResourceReservation;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\ResourceReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class ResourceReservationCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejecting_approved_reservation_deletes_google_calendar_event(): void
    {
        Mail::fake();

        $approver = User::factory()->create();
        $requester = User::factory()->create(['email' => 'requester@example.com']);

        $reservation = ResourceReservation::create([
            'user_id' => $requester->id,
            'requester_email' => $requester->email,
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

        app(ResourceReservationService::class)->rejectReservation(
            $reservation,
            $approver->id,
            'Not available.'
        );

        $reservation->refresh();

        $this->assertSame('rejected', $reservation->status);
        $this->assertNull($reservation->google_event_id);
        $this->assertSame($approver->id, $reservation->approved_by);
        $this->assertSame('Not available.', $reservation->approval_note);
    }

    public function test_rejecting_reservation_stops_when_calendar_delete_fails(): void
    {
        Mail::fake();

        $approver = User::factory()->create();
        $requester = User::factory()->create(['email' => 'requester@example.com']);

        $reservation = ResourceReservation::create([
            'user_id' => $requester->id,
            'requester_email' => $requester->email,
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

        try {
            app(ResourceReservationService::class)->rejectReservation(
                $reservation,
                $approver->id,
                'Not available.'
            );

            $this->fail('Calendar deletion failure should stop rejection.');
        } catch (\Exception $e) {
            $this->assertSame(
                'Unable to delete the Google Calendar event. Reservation was not rejected.',
                $e->getMessage()
            );
        }

        $reservation->refresh();

        $this->assertSame('approved', $reservation->status);
        $this->assertSame('calendar-event-19', $reservation->google_event_id);
    }
}
