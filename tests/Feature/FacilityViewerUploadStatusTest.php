<?php

namespace Tests\Feature;

use App\Models\ResourceReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityViewerUploadStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_tabs_and_recurring_dates_hide_ended_events(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-16 12:00:00', 'Asia/Manila'));
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::findOrCreate('facility.viewer', 'web'));
        foreach (['approved', 'pending'] as $status) {
            foreach ([
                'finished' => [now()->subHours(2), now()->subHour()],
                'just ended' => [now()->subHour(), now()],
                'ongoing' => [now()->subHour(), now()->addHour()],
                'upcoming' => [now()->addDay(), now()->addDay()->addHour()],
            ] as $label => [$start, $end]) {
                ResourceReservation::create(['title' => $status.' '.$label, 'status' => $status,
                    'start_datetime' => $start, 'end_datetime' => $end, 'recurrence_series_id' => 'current-viewer-series']);
            }
        }
        foreach (['approved', 'pending'] as $status) {
            $this->actingAs($viewer)->get(route('resources.reservations.index', ['status' => $status]))
                ->assertOk()->assertSee($status.' ongoing')->assertSee($status.' upcoming')
                ->assertDontSee('approved finished')->assertDontSee('pending finished')
                ->assertDontSee('approved just ended')->assertDontSee('pending just ended');
        }
    }

    public function test_viewer_sees_upload_badges_without_billing_file_paths(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::findOrCreate('facility.viewer', 'web'));
        $booking = ResourceReservation::create(['title' => 'Upload status', 'status' => 'approved',
            'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour(),
            'soa_path' => 'private/secret-soa.pdf', 'recurrence_series_id' => 'viewer-series']);
        $booking->paymentProofs()->create(['path' => 'private/secret-payment.pdf', 'original_name' => 'Secret receipt.pdf']);
        $response = $this->actingAs($viewer)->get(route('resources.reservations.index'));
        $response->assertOk()->assertSee('SOA Uploaded')->assertSee('Proof of Payment Uploaded')
            ->assertDontSee('secret-soa.pdf')->assertDontSee('secret-payment.pdf')->assertDontSee('Secret receipt.pdf')
            ->assertDontSee('billing-file');
        $this->assertSame(2, substr_count($response->getContent(), 'SOA Uploaded'));
        $booking->paymentProofs()->delete();
        $booking->update(['soa_path' => null]);
        $this->get(route('resources.reservations.index'))->assertDontSee('SOA Uploaded')->assertDontSee('Proof of Payment Uploaded');
        $booking->update(['payment_proof_path' => 'legacy-proof.pdf']);
        $this->get(route('resources.reservations.index'))->assertSee('Proof of Payment Uploaded')->assertDontSee('legacy-proof.pdf');
    }
}
