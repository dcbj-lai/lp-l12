<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Models\{ResourceReservation, User};
use App\Services\FacilityReservationArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Mail, Storage};
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityReservationArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('facility.admin', 'web'));
        return $user;
    }

    private function booking(User $owner, array $attributes = []): ResourceReservation
    {
        return ResourceReservation::create(array_merge([
            'user_id' => $owner->id, 'title' => 'Archive QA', 'status' => 'pending',
            'start_datetime' => now()->addDay(), 'end_datetime' => now()->addDay()->addHour(),
        ], $attributes));
    }

    public function test_archive_filter_and_restore_preserve_status_and_other_records(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $booking = $this->booking($admin);
        $other = $this->booking($admin);
        $form = Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->call('archiveReservation', $booking->id);
        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->manually_archived_at);
        $this->assertNull($other->fresh()->manually_archived_at);
        $this->assertFalse($form->instance()->reservations->flatten(1)->contains('id', $booking->id));
        $form->set('archiveFilter', 'archived');
        $this->assertTrue($form->instance()->reservations->flatten(1)->contains('id', $booking->id));
        $form->call('unarchiveReservation', $booking->id);
        $this->assertNull($booking->fresh()->manually_archived_at);
        $this->assertSame('pending', $booking->fresh()->status);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_approved_cannot_be_archived_but_done_and_deleted_can(): void
    {
        $admin = $this->admin();
        $service = app(FacilityReservationArchiveService::class);
        $approved = $this->booking($admin, ['status' => 'approved']);
        try { $service->archive($approved->id, $admin); $this->fail('Approved archive accepted'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('archiveAction', $e->errors()); }
        $done = $this->booking($admin, ['status' => 'approved', 'billing_status' => 'paid', 'end_datetime' => now()->subHour()]);
        $service->archive($done->id, $admin);
        $this->assertNotNull($done->fresh()->manually_archived_at);
        $approved->delete();
        $service->archive($approved->id, $admin);
        $service->archive($approved->id, $admin, true);
        $this->assertTrue(ResourceReservation::withTrashed()->find($approved->id)->trashed());
    }

    public function test_permanent_delete_requires_archive_and_confirmation_and_keeps_shared_floor_plan(): void
    {
        config(['filesystems.facility_upload_disk' => 'local']);
        Storage::fake('local');
        $admin = $this->admin();
        $one = $this->booking($admin, ['floor_plan_path' => 'reservations/shared.pdf', 'gate_pass_path' => 'reservations/only-one.pdf']);
        $two = $this->booking($admin, ['floor_plan_path' => 'reservations/shared.pdf']);
        Storage::disk('local')->put('reservations/shared.pdf', 'shared');
        Storage::disk('local')->put('reservations/only-one.pdf', 'own');
        \App\Models\FacilityBillingEmail::create(['reservation_id' => $one->id, 'requested_by' => $admin->id,
            'kind' => 'soa', 'status' => 'sent', 'snapshot' => ['soa_path' => 'reservations/shared.pdf']]);
        \App\Models\FacilityPaymentProof::create(['reservation_id' => $one->id, 'recorded_by' => $admin->id,
            'path' => 'reservations/only-one.pdf', 'original_name' => 'proof.pdf']);
        try { app(FacilityReservationArchiveService::class)->permanentlyDelete($one->id, $admin); $this->fail('Unarchived delete accepted'); }
        catch (ValidationException $e) { $this->assertNotNull($one->fresh()); }
        $form = Livewire::actingAs($admin)->test(ReservationIndex::class)
            ->call('archiveReservation', $one->id)->call('selectForPermanentDelete', $one->id)
            ->call('confirmPermanentDelete')->assertHasErrors('archiveDeleteConfirmation');
        $this->assertNotNull($one->fresh());
        $form->set('archiveDeleteConfirmation', 'DELETE')->call('confirmPermanentDelete')
            ->assertSet('archiveDeleteId', null)->assertSet('showArchiveDeleteModal', false);
        $this->assertNull(ResourceReservation::withTrashed()->find($one->id));
        $this->assertDatabaseMissing('facility_billing_emails', ['reservation_id' => $one->id]);
        $this->assertDatabaseMissing('facility_payment_proofs', ['reservation_id' => $one->id]);
        $this->assertNotNull($two->fresh());
        Storage::disk('local')->assertExists('reservations/shared.pdf');
        Storage::disk('local')->assertMissing('reservations/only-one.pdf');
        $form->call('confirmPermanentDelete');
        $this->assertNotNull($two->fresh());
    }

    public function test_approver_cannot_archive_or_permanently_delete(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('facility.approver', 'web'));
        $booking = $this->booking($user);
        Livewire::actingAs($user)->test(ReservationIndex::class)->call('archiveReservation', $booking->id)->assertForbidden();
        Livewire::actingAs($user)->test(ReservationIndex::class)->call('selectForPermanentDelete', $booking->id)->assertForbidden();
    }

    public function test_queued_billing_blocks_permanent_deletion(): void
    {
        $admin = $this->admin();
        $booking = $this->booking($admin);
        $service = app(FacilityReservationArchiveService::class);
        $service->archive($booking->id, $admin);
        \App\Models\FacilityBillingEmail::create(['reservation_id' => $booking->id, 'requested_by' => $admin->id,
            'kind' => 'soa', 'status' => 'queued', 'snapshot' => []]);
        try { $service->permanentlyDelete($booking->id, $admin); $this->fail('Queued email deleted'); }
        catch (ValidationException $e) { $this->assertNotNull($booking->fresh()); }
        $this->assertDatabaseHas('facility_billing_emails', ['reservation_id' => $booking->id, 'status' => 'queued']);
    }

    public function test_stale_approval_cannot_approve_an_archived_request(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $booking = $this->booking($admin);
        app(FacilityReservationArchiveService::class)->archive($booking->id, $admin);
        $this->expectExceptionMessage('Restore this reservation from archive before approving it.');
        app(\App\Services\ResourceReservationService::class)->approveReservation($booking, $admin->id);
    }

    public function test_archive_filters_work_across_statuses_and_one_recurring_date_is_independent(): void
    {
        $admin = $this->admin();
        $series = (string) \Illuminate\Support\Str::uuid();
        $one = $this->booking($admin, ['recurrence_series_id' => $series]);
        $two = $this->booking($admin, ['recurrence_series_id' => $series]);
        $rejected = $this->booking($admin, ['status' => 'rejected']);
        $deleted = $this->booking($admin);
        $deleted->delete();
        $done = $this->booking($admin, ['status' => 'approved', 'billing_status' => 'paid', 'end_datetime' => now()->subHour()]);
        foreach ([$one, $rejected, $deleted, $done] as $record) app(FacilityReservationArchiveService::class)->archive($record->id, $admin);
        $this->assertNull($two->fresh()->manually_archived_at);
        $form = Livewire::actingAs($admin)->test(ReservationIndex::class)->set('archiveFilter', 'archived');
        foreach (['pending' => $one, 'rejected' => $rejected, 'deleted' => $deleted, 'archive' => $done] as $tab => $record) {
            $form->set('statusFilter', $tab);
            $ids = $form->instance()->reservations->flatten(1)->pluck('id')->all();
            $this->assertSame([$record->id], $ids);
        }
        $form->set('statusFilter', 'all');
        $this->assertCount(4, $form->instance()->reservations->flatten(1));
        $form->set('archiveFilter', 'all');
        $this->assertCount(5, $form->instance()->reservations->flatten(1));
    }

    public function test_soa_delete_control_hidden_in_cards_and_recurring_dates(): void
    {
        $admin = $this->admin();
        $series = (string) \Illuminate\Support\Str::uuid();
        $open = $this->booking($admin, ['recurrence_series_id' => $series]);
        $protected = $this->booking($admin, ['soa_path' => 'facility-billing/dummy.pdf', 'recurrence_series_id' => $series]);
        $form = Livewire::actingAs($admin)->test(ReservationIndex::class)->call('showRecurringSeries', $series);
        $form->assertSeeHtml('aria-label="Delete reservation #'.$open->id.'"')
            ->assertDontSeeHtml('aria-label="Delete reservation #'.$protected->id.'"');
        $form->set('archiveFilter', 'all');
        $form->assertSeeHtml('aria-label="Delete reservation #'.$open->id.'"')
            ->assertDontSeeHtml('aria-label="Delete reservation #'.$protected->id.'"');
    }

    public function test_calendar_failure_and_inactive_admin_cannot_delete(): void
    {
        $admin = $this->admin();
        $booking = $this->booking($admin, ['google_event_id' => 'local-test-calendar']);
        $service = app(FacilityReservationArchiveService::class);
        $service->archive($booking->id, $admin);
        $this->mock(\App\Services\ResourceReservationService::class)->shouldReceive('deleteGoogleCalendarEvent')->once()->andReturn(false);
        try { $service->permanentlyDelete($booking->id, $admin); $this->fail('Calendar failure ignored'); }
        catch (ValidationException $e) { $this->assertNotNull($booking->fresh()); }
        $admin->update(['is_active' => false]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->permanentlyDelete($booking->id, $admin);
    }

    public function test_viewer_cannot_see_archived_pending_or_use_admin_actions(): void
    {
        $admin = $this->admin();
        $booking = $this->booking($admin, ['title' => 'Hidden archived request']);
        app(FacilityReservationArchiveService::class)->archive($booking->id, $admin);
        $this->booking($admin, ['title' => 'Visible current request']);
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole(Role::findOrCreate('facility.viewer', 'web'));
        $this->actingAs($viewer)->get('/resources/reservations?status=pending')->assertOk()
            ->assertSee('Visible current request')->assertDontSee('Hidden archived request');
        Livewire::actingAs($viewer)->test(ReservationIndex::class)->assertForbidden();
    }
}
