<?php

namespace Tests\Feature;

use App\Models\ResourceReservation;
use App\Models\User;
use Database\Seeders\ResourceRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FacilityViewerTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        $this->seed(ResourceRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('facility.viewer');
        $this->actingAs($user);
        return $user;
    }

    private function reservation(string $status, string $title): ResourceReservation
    {
        return ResourceReservation::create(['title' => $title, 'requester_email' => 'requester@example.test',
            'status' => $status, 'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour(),
            'soa_path' => 'private-soa.pdf', 'payment_proof_path' => 'private-proof.pdf']);
    }

    public function test_viewer_sees_selected_allowed_status_and_never_billing_or_actions(): void
    {
        $this->viewer();
        $this->reservation('approved', 'Approved event');
        $this->reservation('pending', 'Pending event');
        $this->reservation('rejected', 'Rejected event');
        $this->reservation('approved', 'Deleted event')->delete();
        $this->get('/resources/reservations')->assertOk()->assertSee('Approved event')->assertDontSee('Pending event');
        $this->get('/resources/reservations?status=pending')->assertOk()->assertSee('Pending event')
            ->assertDontSee('Approved event')->assertDontSee('Rejected event')->assertDontSee('Deleted event')
            ->assertDontSee('private-soa.pdf')->assertDontSee('private-proof.pdf')->assertDontSee('wire:snapshot', false)
            ->assertDontSee('Approve</button>', false);
    }

    public function test_other_features_and_livewire_are_blocked_even_with_additional_role(): void
    {
        $this->viewer()->assignRole('facility.admin');
        foreach (['/resources', '/resources/book', '/events', '/my-attendance', '/payslips', '/admin/access/users', '/settings/profile-2', '/launcher'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post(route('default-livewire.update'), [])->assertForbidden();
        $this->getJson('/api/user')->assertForbidden();
        $this->getJson('/api/facility-reservations')->assertForbidden();
        $this->post('/attendance/check-in', [])->assertForbidden();
        $this->get('/dashboard')->assertRedirect('/resources/reservations');
        Livewire::test(\App\Livewire\Resources\ReservationIndex::class)->assertForbidden();
    }

    public function test_floor_plans_require_current_approved_or_pending_status(): void
    {
        $this->viewer();
        Storage::fake(config('filesystems.facility_upload_disk'));
        Storage::disk(config('filesystems.facility_upload_disk'))->put('plan.pdf', 'floor plan');
        $reservation = $this->reservation('approved', 'Plan event');
        $reservation->update(['floor_plan_path' => 'plan.pdf']);
        $url = '/resources/reservations/'.$reservation->id.'/floor-plan';
        $this->get($url)->assertOk();
        $reservation->update(['status' => 'pending']);
        $this->get($url)->assertOk();
        $reservation->update(['status' => 'rejected']);
        $this->get($url)->assertForbidden();
        $reservation->delete();
        $this->get($url)->assertNotFound();
    }

    public function test_recurring_dates_show_pending_and_approved_but_hide_deleted_rejected_and_other_series(): void
    {
        $this->viewer();
        foreach (['approved', 'pending', 'rejected'] as $status) {
            $date = $this->reservation($status, 'Series '.$status);
            $date->update(['recurrence_series_id' => 'viewer-series', 'recurrence_label' => 'Daily for 3 dates']);
        }
        $deleted = $this->reservation('approved', 'Deleted series date');
        $deleted->update(['recurrence_series_id' => 'viewer-series']);
        $deleted->delete();
        $this->reservation('pending', 'Unrelated pending event')->update(['recurrence_series_id' => 'other-series']);
        $response = $this->get('/resources/reservations');
        $response->assertOk()->assertSee('View recurring dates')->assertDontSee('private-soa.pdf')->assertDontSee('private-proof.pdf');
        $dates = $response->viewData('seriesDates')->get('viewer-series');
        $this->assertEqualsCanonicalizing(['approved', 'pending'], $dates->pluck('status')->all());
        $this->assertCount(1, $response->viewData('seriesDates'));
        $this->get('/resources/reservations?status=rejected')->assertOk()->assertSee('Series approved')->assertDontSee('Series rejected');
    }

    public function test_admin_view_and_logout_still_work(): void
    {
        $viewer = $this->viewer();
        $this->post('/logout')->assertRedirect('/');
        $viewer->syncRoles(['facility.admin']);
        $this->actingAs($viewer)->get('/resources/reservations')->assertOk()->assertSee('Approval dashboard');
    }
}
