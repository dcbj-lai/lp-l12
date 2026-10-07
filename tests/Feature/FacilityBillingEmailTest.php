<?php

namespace Tests\Feature;

use App\Jobs\SendFacilityBillingEmail;
use App\Mail\FacilityBillingMail;
use App\Models\FacilityBillingEmail;
use App\Models\ResourceReservation;
use App\Models\User;
use App\Services\FacilityBillingEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityBillingEmailTest extends TestCase
{
    use RefreshDatabase;

    private function reservation(array $attributes = []): ResourceReservation
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $this->actingAs($user);
        Storage::fake(config('filesystems.facility_upload_disk'));
        Storage::disk(config('filesystems.facility_upload_disk'))->put('reservations/soa/test.pdf', 'SOA content');
        return ResourceReservation::create(array_merge([
            'title' => 'Billing test', 'requester_email' => 'requester@example.test', 'status' => 'approved',
            'start_datetime' => now()->addMonth(), 'end_datetime' => now()->addMonth()->addHour(),
            'soa_path' => 'reservations/soa/test.pdf', 'soa_email_pending' => true,
        ], $attributes));
    }

    public function test_billing_changes_only_after_successful_send_and_duplicate_job_is_safe(): void
    {
        Queue::fake(); Mail::fake();
        $reservation = $this->reservation();
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        $message = FacilityBillingEmail::firstOrFail();
        $this->assertSame('unbilled', $reservation->fresh()->billing_status);
        Queue::assertPushed(SendFacilityBillingEmail::class);
        $job = new SendFacilityBillingEmail($message->id);
        $job->handle(); $job->handle();
        Mail::assertSent(FacilityBillingMail::class, 1);
        $this->assertSame('sent', $message->fresh()->status);
        $this->assertSame(today()->addDays(15)->toDateString(), $reservation->fresh()->payment_due_at->toDateString());
        $this->assertFalse($reservation->fresh()->soa_email_pending);
        Storage::disk(config('filesystems.facility_upload_disk'))->assertMissing($message->snapshot['attachment']);
    }

    public function test_paid_updated_soa_is_blocked_without_changing_payment(): void
    {
        Mail::fake();
        $reservation = $this->reservation(['billing_status' => 'paid', 'soa_sent_at' => today()->subDays(10), 'payment_due_at' => today()->addDays(5), 'paid_at' => now()]);
        $paidAt = $reservation->paid_at;
        try {
            app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
            $this->fail('Paid SOA email was allowed.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('after payment is recorded', $e->getMessage());
        }
        $this->assertSame('paid', $reservation->fresh()->billing_status);
        $this->assertTrue($reservation->fresh()->paid_at->equalTo($paidAt));
        $this->assertSame(today()->addDays(5)->toDateString(), $reservation->fresh()->payment_due_at->toDateString());
        Mail::assertNothingSent();
        $this->assertSame(0, FacilityBillingEmail::count());
    }

    public function test_reminder_selects_upcoming_due_today_and_overdue_and_suppresses_same_day_repeat(): void
    {
        Mail::fake();
        foreach ([5 => 'upcoming', 0 => 'due_today', -2 => 'overdue'] as $offset => $kind) {
            $reservation = $this->reservation(['soa_email_pending' => false, 'billing_status' => 'billed', 'soa_sent_at' => today()->subDays(10), 'payment_due_at' => today()->addDays($offset)]);
            app(FacilityBillingEmailService::class)->queue($reservation->id, 'reminder');
            $this->assertSame($kind, FacilityBillingEmail::where('reservation_id', $reservation->id)->first()->kind);
            try {
                app(FacilityBillingEmailService::class)->queue($reservation->id, 'reminder');
                $this->fail('A duplicate reminder was allowed.');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('already been sent today', $e->getMessage());
            }
        }
        Mail::assertSent(FacilityBillingMail::class, 3);
    }

    public function test_unpaid_updated_soa_still_sends_but_queued_soa_skips_after_payment(): void
    {
        Mail::fake();
        $reservation = $this->reservation(['billing_status' => 'billed', 'soa_sent_at' => today()->subDays(10), 'payment_due_at' => today()->addDays(5)]);
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        Mail::assertSent(FacilityBillingMail::class, fn ($mail) => $mail->kind === 'updated_soa');
        $this->assertSame(today()->addDays(5)->toDateString(), $reservation->fresh()->payment_due_at->toDateString());

        Queue::fake(); Mail::fake();
        $reservation->refresh()->update(['soa_email_pending' => true]);
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        $message = FacilityBillingEmail::latest('id')->firstOrFail();
        $reservation->update(['paid_at' => now()]);
        (new SendFacilityBillingEmail($message->id))->handle();
        $this->assertSame('skipped', $message->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_paid_or_replaced_reservation_skips_queued_reminder(): void
    {
        Queue::fake(); Mail::fake();
        $reservation = $this->reservation(['soa_email_pending' => false, 'billing_status' => 'billed', 'payment_due_at' => today()->addDays(3)]);
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'reminder');
        $message = FacilityBillingEmail::firstOrFail();
        $reservation->update(['billing_status' => 'paid']);
        (new SendFacilityBillingEmail($message->id))->handle();
        $this->assertSame('skipped', $message->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_replaced_or_removed_soa_does_not_send_stale_attachment(): void
    {
        Queue::fake(); Mail::fake();
        $reservation = $this->reservation();
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        $message = FacilityBillingEmail::firstOrFail();
        $reservation->update(['soa_path' => null]);
        (new SendFacilityBillingEmail($message->id))->handle();
        $this->assertSame('skipped', $message->fresh()->status);
        $this->assertNull($reservation->fresh()->soa_sent_at);
        Mail::assertNothingSent();
    }

    public function test_failure_can_be_requeued_without_marking_reservation_billed(): void
    {
        Queue::fake();
        $reservation = $this->reservation();
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        $message = FacilityBillingEmail::firstOrFail();
        (new SendFacilityBillingEmail($message->id))->failed(new \RuntimeException('Transport failed'));
        $this->assertSame('failed', $message->fresh()->status);
        $this->assertSame('unbilled', $reservation->fresh()->billing_status);
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        $this->assertSame(2, FacilityBillingEmail::count());
    }

    public function test_pending_queue_blocks_duplicate_sending(): void
    {
        Queue::fake();
        $reservation = $this->reservation();
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        $this->expectExceptionMessage('already queued');
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
    }

    public function test_non_admin_cannot_queue_billing_mail(): void
    {
        $reservation = $this->reservation();
        $this->actingAs(User::factory()->create());
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
    }

    public function test_missing_email_and_unsent_replacement_block_mail(): void
    {
        Queue::fake();
        $reservation = $this->reservation(['requester_email' => null]);
        try {
            app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
            $this->fail('Missing recipient was accepted.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('valid requester email', $e->getMessage());
        }
        $reservation->update(['requester_email' => 'requester@example.test', 'billing_status' => 'billed', 'payment_due_at' => today()->addDays(5)]);
        $this->expectExceptionMessage('current SOA sent');
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'reminder');
    }

    public function test_transport_failure_leaves_draft_unbilled(): void
    {
        Queue::fake();
        $reservation = $this->reservation();
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        $message = FacilityBillingEmail::firstOrFail();
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new \RuntimeException('Transport failure'));
        try {
            (new SendFacilityBillingEmail($message->id))->handle();
            $this->fail('A failed mail transport was accepted.');
        } catch (\RuntimeException $e) {
            (new SendFacilityBillingEmail($message->id))->failed($e);
        }
        $this->assertSame('failed', $message->fresh()->status);
        $this->assertSame('unbilled', $reservation->fresh()->billing_status);
        $this->assertNull($reservation->fresh()->payment_due_at);
    }

    public function test_email_previews_are_local_only_and_render_every_scenario(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        foreach (['soa', 'updated_soa', 'upcoming', 'due_today', 'overdue', 'payment_received'] as $scenario) {
            $this->get('/dev/facilities/billing-email/' . $scenario)->assertOk()->assertSee('Faculty Workshop');
        }
        $this->get('/dev/facilities/billing-email/updated_paid')->assertNotFound();
        $this->app->detectEnvironment(fn () => 'production');
        $this->get('/dev/facilities/billing-email')->assertNotFound();
    }

    public function test_daily_command_sends_seven_day_reminder_without_logged_in_user_and_deduplicates(): void
    {
        Mail::fake();
        $due = $this->reservation(['billing_status' => 'billed', 'soa_email_pending' => false, 'payment_due_at' => today()->addDays(7)]);
        $this->reservation(['billing_status' => 'billed', 'soa_email_pending' => false, 'payment_due_at' => today()->addDays(8)]);
        $this->reservation(['billing_status' => 'billed', 'soa_email_pending' => true, 'payment_due_at' => today()->addDays(7)]);
        auth()->forgetUser();
        $this->artisan('facilities:payment-reminders')->assertSuccessful();
        $this->artisan('facilities:payment-reminders')->assertSuccessful();
        Mail::assertSent(FacilityBillingMail::class, 1);
        $message = FacilityBillingEmail::firstOrFail();
        $this->assertSame($due->id, $message->reservation_id);
        $this->assertNull($message->requested_by);
    }

    public function test_bulk_reminders_snapshot_and_recheck_payment_before_sending(): void
    {
        Queue::fake();
        $due = $this->reservation(['billing_status' => 'billed', 'soa_email_pending' => false, 'payment_due_at' => today()->addDays(7)]);
        $paid = $this->reservation(['billing_status' => 'paid', 'soa_email_pending' => false, 'payment_due_at' => today()->addDays(7)]);
        $component = \Livewire\Livewire::test(\App\Livewire\Resources\ReservationIndex::class)->call('selectBulkReminders');
        $this->assertSame([$due->id], $component->get('bulkReminderIds'));
        $due->update(['paid_at' => now()]);
        $component->call('sendBulkReminders')->call('sendBulkReminders');
        Queue::assertNothingPushed();
        $this->assertSame(0, FacilityBillingEmail::count());
    }

    public function test_reupload_resets_due_date_and_delayed_email_preserves_upload_due_date(): void
    {
        Mail::fake();
        $this->travelTo(now()->setDate(2026, 10, 7)->setTime(10, 0));
        $reservation = $this->reservation(['billing_status' => 'billed', 'soa_sent_at' => today()->subDays(5), 'payment_due_at' => today()->addDays(10)]);
        $component = \Livewire\Livewire::test(\App\Livewire\Resources\ReservationIndex::class)
            ->call('selectForBilling', $reservation->id)
            ->set('soaFile', \Illuminate\Http\UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'))
            ->call('markBilled')->assertHasNoErrors();
        $this->assertSame('2026-10-22', $reservation->fresh()->payment_due_at->toDateString());
        $this->travel(2)->days();
        $component->call('selectForBilling', $reservation->id)
            ->set('soaFile', \Illuminate\Http\UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'))
            ->call('markBilled')->assertHasNoErrors();
        $this->assertSame('2026-10-24', $reservation->fresh()->payment_due_at->toDateString());
        $this->travel(1)->days();
        app(FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        $this->assertSame('2026-10-24', $reservation->fresh()->payment_due_at->toDateString());
        Mail::assertSent(FacilityBillingMail::class, fn ($mail) => str_contains($mail->render(), 'Oct 24, 2026'));
    }
}
