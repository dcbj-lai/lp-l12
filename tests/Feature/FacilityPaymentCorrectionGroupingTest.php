<?php

namespace Tests\Feature;

use App\Models\{FacilityPaymentSubmission, ResourceReservation, User};
use App\Services\FacilityPaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityPaymentCorrectionGroupingTest extends TestCase
{
    use RefreshDatabase;

    private function booking(): ResourceReservation
    {
        return ResourceReservation::create(['title' => 'Repeated correction event', 'status' => 'approved',
            'requester_email' => 'requester@example.test', 'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour()]);
    }

    private function submission(ResourceReservation $booking, string $reference, string $status = 'correction'): FacilityPaymentSubmission
    {
        return $booking->paymentSubmissions()->create(['status' => $status, 'paid_on' => today(), 'reference' => $reference,
            'files' => [], 'soa_path' => 'dummy.pdf', 'review_note' => 'Review '.$reference, 'reviewed_at' => now()]);
    }

    private function signIn(): void
    {
        $verifier = User::factory()->create();
        $verifier->assignRole(Role::findOrCreate(FacilityPaymentVerificationService::ROLE, 'web'));
        $this->actingAs($verifier);
    }

    public function test_one_card_per_reservation_shows_latest_correction_and_retains_full_detail_history(): void
    {
        $this->signIn();
        $booking = $this->booking();
        $this->submission($booking, 'EARLIER-ONE');
        $this->submission($booking, 'EARLIER-TWO');
        $latest = $this->submission($booking, 'LATEST-THREE');
        // Identical event names must not merge different reservations/recurring dates.
        $other = $this->booking();
        $this->submission($other, 'OTHER-DATE');
        $this->get(route('facilities.payments.index', ['status' => 'correction']))->assertOk()
            ->assertViewHas('submissions', fn ($rows) => $rows->total() === 2 && $rows->contains('id', $latest->id))
            ->assertSee('3 corrections')->assertSee('LATEST-THREE')->assertSee('OTHER-DATE')
            ->assertDontSee('EARLIER-ONE')->assertDontSee('EARLIER-TWO');
        $this->get(route('facilities.payments.review', $booking))->assertOk()
            ->assertSee('EARLIER-ONE')->assertSee('EARLIER-TWO')->assertSee('LATEST-THREE');
        $this->assertSame(4, FacilityPaymentSubmission::count());
    }

    public function test_pagination_counts_events_before_paginating_and_does_not_split_duplicate_corrections(): void
    {
        $this->signIn();
        for ($i = 0; $i < 21; $i++) $this->submission($this->booking(), 'OTHER-'.$i);
        $repeated = $this->booking();
        for ($i = 0; $i < 25; $i++) $this->submission($repeated, 'REPEAT-'.$i);
        $first = $this->get(route('facilities.payments.index', ['status' => 'correction']))->assertOk();
        $rows = $first->viewData('submissions');
        $this->assertSame(22, $rows->total());
        $this->assertCount(20, $rows);
        $this->assertSame(20, $rows->pluck('reservation_id')->unique()->count());
        $this->assertSame(25, $rows->first()->reservation->correction_count);
        $second = $this->get(route('facilities.payments.index', ['status' => 'correction', 'page' => 2]))->assertOk()->viewData('submissions');
        $this->assertCount(2, $second);
        $this->assertFalse($second->contains('reservation_id', $repeated->id));
        $this->assertCount(0, $rows->pluck('reservation_id')->intersect($second->pluck('reservation_id')));
    }

    public function test_other_status_lists_remain_submission_based_and_unauthorized_users_are_denied(): void
    {
        $this->signIn();
        $booking = $this->booking();
        foreach (['pending', 'confirmed', 'superseded'] as $status) {
            $this->submission($booking, $status.'-one', $status);
            $this->submission($booking, $status.'-two', $status);
            $this->get(route('facilities.payments.index', ['status' => $status]))->assertOk()
                ->assertViewHas('submissions', fn ($rows) => $rows->total() === 2)
                ->assertSee($status.'-one')->assertSee($status.'-two');
        }
        $this->actingAs(User::factory()->create())->get(route('facilities.payments.index', ['status' => 'correction']))->assertForbidden();
    }
}
