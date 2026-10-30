<?php

namespace Tests\Feature;

use App\Models\FacilityReservationAccessLink;
use App\Models\FacilityReservationCancellation;
use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Models\User;
use App\Services\FacilityRequesterCancellationService;
use App\Services\GoogleCalendarService;
use App\Services\ResourceReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FacilityRequesterCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function booking(array $changes = []): ResourceReservation
    {
        return ResourceReservation::create($changes + ['title' => 'Cancellation test', 'requester_email' => 'organizer@example.test',
            'status' => 'pending', 'billing_status' => 'unbilled', 'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour()]);
    }

    private function token(ResourceReservation $booking): string
    {
        return basename(parse_url(app(FacilityRequesterCancellationService::class)->link($booking), PHP_URL_PATH));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_guest_get_does_not_cancel_and_post_requires_confirmation_and_is_idempotent(): void
    {
        $booking = $this->booking();
        $token = $this->token($booking);
        $this->get(route('facilities.requester.show', $token))->assertOk()->assertSee('Cancellation test')->assertDontSee('SOA Uploaded');
        $this->assertFalse($booking->fresh()->trashed());
        $url = route('facilities.requester.cancel', $token);
        $data = ['scope' => 'date', 'reservation_id' => $booking->id, 'reason' => 'Plans changed'];
        $this->post($url, $data)->assertSessionHasErrors('confirm');
        $this->post($url, $data + ['confirm' => 1])->assertRedirect();
        $this->assertSoftDeleted($booking);
        $this->assertDatabaseHas('facility_reservation_cancellations', ['reservation_id' => $booking->id, 'status' => 'cancelled', 'reason' => 'Plans changed']);
        $this->post($url, $data + ['confirm' => 1])->assertRedirect();
        $this->assertSame(1, FacilityReservationCancellation::count());
        $booking->restore();
        $this->post($url, $data + ['confirm' => 1])->assertRedirect();
        $this->assertFalse($booking->fresh()->trashed());
    }

    public function test_links_are_hashed_expire_and_cannot_access_another_booking(): void
    {
        $booking = $this->booking();
        $other = $this->booking(['title' => 'Other private event', 'requester_email' => 'other@example.test']);
        $token = $this->token($booking);
        $this->assertSame(hash('sha256', $token), FacilityReservationAccessLink::first()->getRawOriginal('token_hash'));
        $this->get(route('facilities.requester.show', str_repeat('x', 64)))->assertNotFound();
        $this->post(route('facilities.requester.cancel', $token), ['scope' => 'date', 'reservation_id' => $other->id, 'confirm' => 1])->assertNotFound();
        $booking->update(['requester_email' => 'new@example.test']);
        $this->get(route('facilities.requester.show', $token))->assertNotFound();
        $booking->update(['requester_email' => 'organizer@example.test']);
        FacilityReservationAccessLink::query()->update(['expires_at' => now()->subSecond()]);
        $this->get(route('facilities.requester.show', $token))->assertNotFound();
    }

    public function test_recurring_link_displays_its_associated_event_and_can_cancel_only_that_date(): void
    {
        $first = $this->booking(['title' => 'Associated date', 'recurrence_series_id' => 'test-series']);
        $other = $this->booking(['title' => 'Other recurring date', 'recurrence_series_id' => 'test-series',
            'start_datetime' => now()->addDays(30), 'end_datetime' => now()->addDays(30)->addHour()]);
        $token = $this->token($first);
        $link = FacilityReservationAccessLink::firstOrFail();
        $this->assertTrue($link->expires_at->equalTo($first->end_datetime->copy()->addDays(7)));
        $this->get(route('facilities.requester.show', $token))->assertOk()->assertSee('Associated date')
            ->assertDontSee('Other recurring date')->assertDontSee('Cancel all upcoming dates');
        $this->post(route('facilities.requester.cancel', $token), ['scope' => 'date', 'confirm' => 1,
            'reservation_id' => $other->id])->assertNotFound();
        $this->assertFalse($first->fresh()->trashed());
        $this->assertFalse($other->fresh()->trashed());
        $this->post(route('facilities.requester.cancel', $token), ['scope' => 'date', 'confirm' => 1,
            'reservation_id' => $first->id])->assertRedirect();
        $this->assertSoftDeleted($first);
        $this->assertFalse($other->fresh()->trashed());
        $this->assertSame(1, FacilityReservationCancellation::count());
    }

    public function test_series_cancellation_only_affects_confirmed_upcoming_dates_and_keeps_billed_dates_for_review(): void
    {
        $first = $this->booking(['recurrence_series_id' => 'test-series']);
        $billed = $this->booking(['recurrence_series_id' => 'test-series', 'status' => 'approved', 'soa_path' => 'test-soa.pdf']);
        $past = $this->booking(['recurrence_series_id' => 'test-series', 'start_datetime' => now()->subHour()]);
        $rejected = $this->booking(['recurrence_series_id' => 'test-series', 'status' => 'rejected']);
        $deleted = $this->booking(['recurrence_series_id' => 'test-series']);
        $deleted->delete();
        $foreign = $this->booking(['recurrence_series_id' => 'test-series', 'requester_email' => 'other@example.test']);
        $unrelated = $this->booking(['recurrence_series_id' => 'other-series']);
        $token = $this->token($first);
        $this->get(route('facilities.requester.show', $token))->assertOk()
            ->assertSee('Select or review upcoming series dates')->assertSee('Requires facilities review')
            ->assertSee('name="scope" value="selected"', false)->assertDontSee('What would you like to cancel?');
        $addedAfterPage = $this->booking(['recurrence_series_id' => 'test-series']);
        $url = route('facilities.requester.cancel', $token);
        $data = ['scope' => 'series', 'reservation_id' => $first->id, 'confirm' => 1, 'reason' => 'Series cancelled',
            'booking_ids' => [$first->id, $billed->id, $past->id, $rejected->id, $deleted->id, $foreign->id, $unrelated->id]];
        $this->post($url, $data)->assertRedirect();
        $this->assertSoftDeleted($first);
        foreach ([$billed, $past, $rejected, $foreign, $unrelated, $addedAfterPage] as $booking) $this->assertFalse($booking->fresh()->trashed());
        $this->assertDatabaseHas('facility_reservation_cancellations', ['reservation_id' => $billed->id, 'status' => 'review']);
        $this->assertSame('test-soa.pdf', $billed->fresh()->soa_path);
        $this->assertSame(2, FacilityReservationCancellation::count());
        $this->post($url, $data)->assertRedirect();
        $this->assertSame(2, FacilityReservationCancellation::count());
    }

    public function test_remaining_series_can_be_cancelled_after_the_linked_date_was_cancelled(): void
    {
        $first = $this->booking(['recurrence_series_id' => 'test-series']);
        $second = $this->booking(['recurrence_series_id' => 'test-series']);
        $token = $this->token($first);
        $url = route('facilities.requester.cancel', $token);
        $this->post($url, ['scope' => 'date', 'reservation_id' => $first->id, 'confirm' => 1])->assertRedirect();
        $this->get(route('facilities.requester.show', $token))->assertOk()
            ->assertSee('name="selected_booking_ids[]" value="'.$second->id.'"', false)
            ->assertDontSee('name="selected_booking_ids[]" value="'.$first->id.'"', false);
        $this->post($url, ['scope' => 'series', 'reservation_id' => $first->id, 'booking_ids' => [$second->id], 'confirm' => 1])->assertRedirect();
        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
    }

    public function test_series_cancellation_requires_recurring_booking_and_submitted_dates(): void
    {
        $first = $this->booking();
        $token = $this->token($first);
        $this->get(route('facilities.requester.show', $token))->assertDontSee('Select or review upcoming series dates');
        $url = route('facilities.requester.cancel', $token);
        $this->post($url, ['scope' => 'series', 'reservation_id' => $first->id, 'confirm' => 1])->assertSessionHasErrors('booking_ids');
        $this->post($url, ['scope' => 'series', 'reservation_id' => $first->id, 'booking_ids' => [$first->id], 'confirm' => 1])->assertStatus(422);
        $this->assertFalse($first->fresh()->trashed());
    }

    public function test_specific_dates_cancels_only_selected_eligible_dates_and_routes_billing_to_review(): void
    {
        $first = $this->booking(['recurrence_series_id' => 'chosen-series']);
        $chosen = $this->booking(['recurrence_series_id' => 'chosen-series']);
        $billed = $this->booking(['recurrence_series_id' => 'chosen-series', 'status' => 'approved', 'soa_path' => 'test.pdf']);
        $unselected = $this->booking(['recurrence_series_id' => 'chosen-series']);
        $foreign = $this->booking(['recurrence_series_id' => 'chosen-series', 'requester_email' => 'other@example.test']);
        $otherSeries = $this->booking(['recurrence_series_id' => 'other-series']);
        $past = $this->booking(['recurrence_series_id' => 'chosen-series', 'start_datetime' => now()->subHour()]);
        $token = $this->token($first);
        $data = ['scope' => 'selected', 'reservation_id' => $first->id, 'confirm' => 1,
            'selected_booking_ids' => [$chosen->id, $billed->id, $foreign->id, $otherSeries->id, $past->id], 'reason' => 'Only chosen dates'];
        $url = route('facilities.requester.cancel', $token);
        $this->post($url, $data)->assertRedirect();
        $this->assertSoftDeleted($chosen);
        foreach ([$first, $billed, $unselected, $foreign, $otherSeries, $past] as $booking) $this->assertFalse($booking->fresh()->trashed());
        $this->assertDatabaseHas('facility_reservation_cancellations', ['reservation_id' => $billed->id, 'status' => 'review']);
        $this->assertSame(2, FacilityReservationCancellation::count());
        $this->post($url, $data)->assertRedirect();
        $this->assertSame(2, FacilityReservationCancellation::count());
    }

    public function test_specific_dates_requires_at_least_one_selection_and_a_recurring_event(): void
    {
        $first = $this->booking(['recurrence_series_id' => 'chosen-series']);
        $token = $this->token($first);
        $url = route('facilities.requester.cancel', $token);
        $data = ['scope' => 'selected', 'reservation_id' => $first->id, 'confirm' => 1];
        $this->post($url, $data)->assertSessionHasErrors('selected_booking_ids');
        $this->post($url, $data + ['selected_booking_ids' => []])->assertSessionHasErrors('selected_booking_ids');
        $this->post($url, $data + ['selected_booking_ids' => [$first->id, $first->id]])->assertSessionHasErrors('selected_booking_ids.0');
        $this->assertFalse($first->fresh()->trashed());
        $single = $this->booking();
        $singleToken = $this->token($single);
        $this->post(route('facilities.requester.cancel', $singleToken), ['scope' => 'selected', 'reservation_id' => $single->id,
            'confirm' => 1, 'selected_booking_ids' => [$single->id]])->assertStatus(422);
        $this->assertFalse($single->fresh()->trashed());
    }

    public function test_billing_requires_admin_review_and_preserves_payment_evidence(): void
    {
        $booking = $this->booking(['status' => 'approved', 'billing_status' => 'paid', 'soa_path' => 'soa.pdf', 'payment_proof_path' => 'paid.pdf', 'paid_at' => now()]);
        $token = $this->token($booking);
        $url = route('facilities.requester.cancel', $token);
        $data = ['scope' => 'date', 'reservation_id' => $booking->id, 'confirm' => 1];
        $this->post($url, $data)->assertRedirect();
        $this->post($url, $data)->assertRedirect();
        $this->assertFalse($booking->fresh()->trashed());
        $this->assertSame(1, FacilityReservationCancellation::count());
        $request = FacilityReservationCancellation::first();
        $this->assertSame('review', $request->status);
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('facility.admin', 'web'));
        $this->actingAs($admin);
        app(FacilityRequesterCancellationService::class)->review($request->id, true);
        $this->assertSoftDeleted($booking);
        $this->assertSame('paid', $booking->fresh()->billing_status);
        $this->assertSame('soa.pdf', $booking->fresh()->soa_path);
        $this->assertSame('paid.pdf', $booking->fresh()->payment_proof_path);
    }

    public function test_calendar_failure_leaves_booking_and_capacity_unchanged(): void
    {
        $user = User::factory()->create();
        $room = Resource::create(['name' => 'Cancellation room', 'type' => 'room', 'capacity' => 10, 'created_by' => $user->id]);
        $booking = $this->booking(['resource_id' => $room->id, 'status' => 'approved', 'google_event_id' => 'calendar-test']);
        $token = $this->token($booking);
        $this->mock(GoogleCalendarService::class, fn ($mock) => $mock->shouldReceive('deleteEvent')->once()->andThrow(new \RuntimeException('Calendar unavailable')));
        $this->post(route('facilities.requester.cancel', $token), ['scope' => 'date', 'reservation_id' => $booking->id, 'confirm' => 1])->assertRedirect();
        $this->assertFalse($booking->fresh()->trashed());
        $this->assertSame('calendar-test', $booking->fresh()->google_event_id);
        $this->assertFalse(app(ResourceReservationService::class)->isResourceAvailable($room->id, $booking->start_datetime, $booking->end_datetime));
        $this->assertSame(0, FacilityReservationCancellation::count());
    }

    public function test_approved_cancellation_removes_calendar_and_releases_capacity(): void
    {
        $user = User::factory()->create();
        $room = Resource::create(['name' => 'Cancellation room', 'type' => 'room', 'capacity' => 10, 'created_by' => $user->id]);
        $booking = $this->booking(['resource_id' => $room->id, 'status' => 'approved', 'google_event_id' => 'calendar-test']);
        $token = $this->token($booking);
        $this->mock(GoogleCalendarService::class, fn ($mock) => $mock->shouldReceive('deleteEvent')->once()->with('calendar-test')->andReturnNull());
        $this->post(route('facilities.requester.cancel', $token), ['scope' => 'date', 'reservation_id' => $booking->id, 'confirm' => 1])->assertRedirect();
        $this->assertSoftDeleted($booking);
        $this->assertNull($booking->fresh()->google_event_id);
        $this->assertTrue(app(ResourceReservationService::class)->isResourceAvailable($room->id, $booking->start_datetime, $booking->end_datetime));
        Mail::assertQueued(\App\Mail\FacilityReservationCancellationMail::class);
    }
}
