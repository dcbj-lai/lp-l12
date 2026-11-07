<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Models\{ResourceReservation, User};
use App\Services\FacilityPaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Mail, Storage};
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityPaymentOverrideTest extends TestCase
{
    use RefreshDatabase;

    private function setupBooking(): array
    {
        config(['filesystems.facility_billing_disk' => 'local']); Storage::fake('local'); Mail::fake();
        $admin = User::factory()->create(); $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $this->actingAs($admin);
        Storage::disk('local')->put('facility-billing/soa/override.pdf', 'SOA');
        $booking = ResourceReservation::create(['title' => 'Override QA', 'status' => 'approved', 'billing_status' => 'billed',
            'requester_email' => 'requester@example.test', 'soa_path' => 'facility-billing/soa/override.pdf',
            'start_datetime' => now()->subDays(2), 'end_datetime' => now()->subDay()]);
        return [$admin, $booking];
    }

    public function test_paid_done_override_requires_reason_and_new_proof_and_retains_previous_evidence(): void
    {
        [$admin, $booking] = $this->setupBooking();
        Storage::disk('local')->put('facility-billing/payments/original.pdf', 'original');
        $old = $booking->paymentProofs()->create(['path' => 'facility-billing/payments/original.pdf', 'original_name' => 'original.pdf', 'paid_on' => today()]);
        $booking->update(['billing_status' => 'paid', 'paid_at' => today(), 'payment_proof_path' => $old->path]);
        $component = Livewire::test(ReservationIndex::class)->call('selectForPayment', $booking->id)->set('paymentOverride', true)
            ->set('paymentProofs', [UploadedFile::fake()->create('replacement.pdf', 2, 'application/pdf')]);
        $component->call('recordPayment')->assertHasErrors('paymentOverrideReason');
        $this->assertSame(1, $booking->paymentProofs()->count());
        $component->set('paymentOverrideReason', 'Corrected receipt')->set('paymentProofs', [])->call('recordPayment')->assertHasErrors('paymentProofs');
        $component->set('paymentProofs', [UploadedFile::fake()->create('replacement.pdf', 2, 'application/pdf')])->call('recordPayment')->assertHasNoErrors();
        $this->assertTrue($booking->fresh()->is_archived);
        $this->assertNotSame($old->path, $booking->fresh()->payment_proof_path);
        $this->assertNotNull($old->fresh()->superseded_at);
        $this->assertSame($admin->id, $old->fresh()->superseded_by);
        $audit = $booking->paymentOverrides()->firstOrFail();
        $this->assertSame([$old->id], $audit->previous_proof_ids);
        $this->assertSame('Corrected receipt', $audit->reason);
        $this->assertSame($admin->id, $audit->overridden_by);
        Storage::disk('local')->assertExists($old->path);
        Mail::assertNothingQueued();
    }

    public function test_override_supersedes_pending_review_and_normal_recording_cannot_bypass_it(): void
    {
        [$admin, $booking] = $this->setupBooking();
        $service = app(FacilityPaymentVerificationService::class);
        $submission = $service->submit(basename($service->link($booking)), ['paid_on' => today()->toDateString(), 'reference' => 'QA',
            'proofs' => [UploadedFile::fake()->create('requester.pdf', 2, 'application/pdf')]]);
        $component = Livewire::test(ReservationIndex::class)->call('selectForPayment', $booking->id)->call('recordPayment')->assertHasErrors('paymentProof');
        $component->set('paymentOverride', true)->set('paymentOverrideReason', 'Manager verified alternate receipt')
            ->set('paymentProofs', [UploadedFile::fake()->create('verified.pdf', 2, 'application/pdf')])->call('recordPayment')->assertHasNoErrors();
        $this->assertSame('paid', $booking->fresh()->billing_status);
        $this->assertSame('superseded', $submission->fresh()->status);
        $this->assertSame($admin->id, $submission->fresh()->reviewed_by);
        $this->assertSame([$submission->id], $booking->paymentOverrides()->firstOrFail()->submission_ids);
        Storage::disk('local')->assertExists($submission->files[0]['path']);
        $verifier = User::factory()->create(); $verifier->assignRole('facility.payment-verifier');
        $this->actingAs($verifier)->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed', 'confirm_full_payment' => 1])->assertSessionHasErrors('decision');
    }

    public function test_approver_without_admin_cannot_override(): void
    {
        [, $booking] = $this->setupBooking();
        $approver = User::factory()->create(); $approver->assignRole(Role::findOrCreate('facility.approver', 'web'));
        Livewire::actingAs($approver)->test(ReservationIndex::class)->set('paymentId', $booking->id)->set('paymentOverride', true)->call('recordPayment')->assertForbidden();
    }

    public function test_opening_payment_resets_previous_override_and_upload_state(): void
    {
        [, $booking] = $this->setupBooking();
        Livewire::test(ReservationIndex::class)->set('paymentOverride', true)->set('paymentOverrideReason', 'Old reason')
            ->set('paymentProofs', [UploadedFile::fake()->create('old.pdf', 2, 'application/pdf')])
            ->call('selectForPayment', $booking->id)->assertSet('paymentOverride', false)->assertSet('paymentOverrideReason', '')
            ->assertSet('paymentProofs', [])->assertSet('paymentId', $booking->id);
    }
}
