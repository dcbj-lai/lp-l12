<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Models\ResourceReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilitySoaEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake(config('filesystems.facility_upload_disk'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $this->actingAs($admin);
    }

    private function reservation(array $overrides = []): ResourceReservation
    {
        return ResourceReservation::create(array_merge([
            'title' => 'SOA edge case', 'requester_email' => 'qa@example.test',
            'status' => 'approved', 'billing_status' => 'unbilled',
            'start_datetime' => now()->subDays(2), 'end_datetime' => now()->subDay(),
        ], $overrides));
    }

    public function test_done_no_payment_booking_moves_to_approved_and_payment_returns_it_to_done(): void
    {
        $reservation = $this->reservation(['finished_confirmed_at' => now(), 'finished_confirmed_by' => auth()->id()]);
        $this->assertTrue($reservation->is_archived);
        $component = Livewire::test(ReservationIndex::class)->set('statusFilter', 'archive')
            ->call('selectForBilling', $reservation->id)->assertSet('billingFromDone', true)
            ->assertSee('This booking will move from Done to Approved')
            ->set('soaFile', UploadedFile::fake()->create('soa.pdf', 10, 'application/pdf'))
            ->call('markBilled')->assertHasNoErrors()->assertSet('statusFilter', 'approved')
            ->assertDispatched('flash', fn ($name, $parameters) => str_contains($parameters['message'], 'View: Approved'));
        $reservation->refresh();
        $this->assertNull($reservation->finished_confirmed_at);
        $this->assertNull($reservation->finished_confirmed_by);
        $this->assertFalse($reservation->is_archived);
        $this->assertSame('approved', $reservation->status);
        $this->assertSame('unbilled', $reservation->billing_status);
        $this->assertContains($reservation->id, $component->instance()->reservations->flatten(1)->pluck('id'));
        $component->call('selectForPayment', $reservation->id)
            ->set('paymentDate', today()->toDateString())
            ->set('paymentProofs', [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')])
            ->call('recordPayment')->assertHasNoErrors()->assertSet('statusFilter', 'archive')
            ->assertDispatched('flash', fn ($name, $parameters) => str_contains($parameters['message'], 'View: Done'));
        $this->assertTrue($reservation->fresh()->is_archived);
    }

    public function test_upload_navigation_covers_unbilled_billed_paid_and_done_for_recurring_dates(): void
    {
        foreach ([['unbilled', false, 'approved'], ['billed', false, 'billed'], ['paid', false, 'paid'], ['paid', true, 'archive']] as [$billing, $past, $tab]) {
            $oldPath = 'soa/'.uniqid().'.pdf';
            Storage::disk(config('filesystems.facility_upload_disk'))->put($oldPath, 'existing SOA');
            $reservation = $this->reservation([
                'billing_status' => $billing, 'soa_path' => $oldPath,
                'recurrence_series_id' => (string) \Illuminate\Support\Str::uuid(), 'recurrence_total' => 2,
                'start_datetime' => $past ? now()->subDays(2) : now()->addDay(),
                'end_datetime' => $past ? now()->subDay() : now()->addDays(2),
                'paid_at' => $billing === 'paid' ? today()->subDay() : null,
                'payment_due_at' => today()->addDays(5),
            ]);
            $oldPaidAt = $reservation->paid_at;
            Livewire::test(ReservationIndex::class)->set('statusFilter', 'archive')
                ->call('selectForBilling', $reservation->id)
                ->set('soaFile', UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'))
                ->set('soaReplacementReason', 'Corrected details')
                ->call('markBilled')->assertHasNoErrors()->assertSet('statusFilter', $tab);
            $reservation->refresh();
            $this->assertSame($billing, $reservation->billing_status);
            $this->assertSame('approved', $reservation->status);
            $this->assertSame($billing === 'paid' ? today()->addDays(5)->toDateString() : today()->addDays(15)->toDateString(), $reservation->payment_due_at->toDateString());
            $this->assertEquals($oldPaidAt, $reservation->paid_at);
            Storage::disk(config('filesystems.facility_upload_disk'))->assertExists($oldPath);
        }
    }

    public function test_missing_unsupported_oversized_and_empty_reason_uploads_do_not_change_done_booking(): void
    {
        foreach ([null, UploadedFile::fake()->create('soa.txt', 10, 'text/plain'), UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')] as $file) {
            $reservation = $this->reservation(['finished_confirmed_at' => now()]);
            $component = Livewire::test(ReservationIndex::class)->set('statusFilter', 'archive')->call('selectForBilling', $reservation->id);
            if ($file) { $component->set('soaFile', $file); }
            $component->call('markBilled')->assertHasErrors(['soaFile'])->assertSet('statusFilter', 'archive');
            $reservation->refresh();
            $this->assertNull($reservation->soa_path);
            $this->assertTrue($reservation->is_archived);
        }
        $reservation = $this->reservation(['soa_path' => 'existing.pdf', 'payment_due_at' => today()->addDays(4)]);
        Livewire::test(ReservationIndex::class)->call('selectForBilling', $reservation->id)
            ->set('soaFile', UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'))
            ->set('soaReplacementReason', '   ')->call('markBilled')->assertHasErrors(['soaReplacementReason']);
        $this->assertSame('existing.pdf', $reservation->fresh()->soa_path);
        $this->assertSame(0, $reservation->soaRevisions()->count());
    }

    public function test_pdf_and_word_uploads_are_accepted_and_cancel_does_not_change_booking(): void
    {
        foreach (['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'] as $extension => $mime) {
            $reservation = $this->reservation();
            $component = Livewire::test(ReservationIndex::class)->call('selectForBilling', $reservation->id)
                ->set('soaFile', UploadedFile::fake()->create('soa.'.$extension, 10, $mime));
            $this->assertNull($reservation->fresh()->soa_path);
            $component->call('markBilled')->assertHasNoErrors();
            Storage::disk(config('filesystems.facility_upload_disk'))->assertExists($reservation->fresh()->soa_path);
        }
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_non_admin_pending_rejected_deleted_and_stale_status_cannot_upload_soa(): void
    {
        foreach (['pending', 'rejected'] as $status) {
            $reservation = $this->reservation(['status' => $status, 'soa_path' => 'soa/legacy.pdf']);
            Livewire::test(ReservationIndex::class)->set('statusFilter', $status)
                ->assertDontSeeHtml('wire:click="selectForPayment('.$reservation->id.')"');
            Livewire::test(ReservationIndex::class)->call('selectForBilling', $reservation->id)->assertStatus(422);
        }
        $deleted = $this->reservation();
        $deleted->delete();
        try {
            Livewire::test(ReservationIndex::class)->call('selectForBilling', $deleted->id);
            $this->fail('Deleted reservations must not open the upload dialog.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertSame(ResourceReservation::class, $exception->getModel());
        }
        $reservation = $this->reservation();
        $component = Livewire::test(ReservationIndex::class)->call('selectForBilling', $reservation->id)
            ->set('soaFile', UploadedFile::fake()->create('soa.pdf', 10, 'application/pdf'));
        $reservation->update(['status' => 'rejected']);
        $component->call('markBilled')->assertStatus(422);
        $this->assertNull($reservation->fresh()->soa_path);
        $this->actingAs(User::factory()->create());
        Livewire::test(ReservationIndex::class)->assertStatus(403);
    }

    public function test_storage_failure_retains_original_file_due_date_and_status(): void
    {
        $disk = Storage::disk(config('filesystems.facility_upload_disk'));
        $disk->put('soa/original.pdf', 'Original file');
        $reservation = $this->reservation(['soa_path' => 'soa/original.pdf', 'billing_status' => 'billed', 'payment_due_at' => today()->addDays(3)]);
        $component = Livewire::test(ReservationIndex::class)->set('statusFilter', 'billed')
            ->call('selectForBilling', $reservation->id)
            ->set('soaReplacementReason', 'Corrected statement')
            ->set('soaFile', UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'));
        $failedDisk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $failedDisk->shouldReceive('put')->once()->andReturn(false);
        $failedDisk->shouldReceive('exists')->once()->andReturn(false);
        $failedDisk->shouldReceive('delete')->once()->andReturn(true);
        $failedDisk->shouldReceive('url')->andReturn('/storage/soa/original.pdf');
        $manager = Storage::getFacadeRoot();
        Storage::shouldReceive('disk')->with(config('filesystems.facility_upload_disk'))->andReturn($failedDisk);
        Storage::shouldReceive('disk')->withArgs(fn ($name = null) => $name !== config('filesystems.facility_upload_disk'))
            ->andReturnUsing(fn ($name = null) => $manager->disk($name));
        $component->call('markBilled')->assertHasErrors(['soaFile'])->assertSet('statusFilter', 'billed');
        $reservation->refresh();
        $this->assertSame('soa/original.pdf', $reservation->soa_path);
        $this->assertSame('billed', $reservation->billing_status);
        $this->assertSame(today()->addDays(3)->toDateString(), $reservation->payment_due_at->toDateString());
        $this->assertSame(0, $reservation->soaRevisions()->count());
        $disk->assertExists('soa/original.pdf');
    }

    public function test_removing_unpaid_soa_does_not_silently_reapply_old_no_payment_confirmation(): void
    {
        $disk = Storage::disk(config('filesystems.facility_upload_disk'));
        $disk->put('soa/original.pdf', 'Original file');
        $reservation = $this->reservation(['soa_path' => 'soa/original.pdf', 'finished_confirmed_at' => now(), 'billing_status' => 'billed']);
        Livewire::test(ReservationIndex::class)->set('statusFilter', 'billed')
            ->call('selectForSoaRemoval', $reservation->id)->call('confirmSoaRemoval')
            ->assertSet('statusFilter', 'approved')->assertHasNoErrors();
        $reservation->refresh();
        $this->assertNull($reservation->soa_path);
        $this->assertNull($reservation->finished_confirmed_at);
        $this->assertFalse($reservation->is_archived);
    }
}
