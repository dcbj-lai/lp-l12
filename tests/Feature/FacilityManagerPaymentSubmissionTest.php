<?php

namespace Tests\Feature;

use App\Livewire\Resources\ReservationIndex;
use App\Models\{ResourceReservation, User};
use App\Services\FacilityPaymentVerificationService as Payments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Mail, Storage};
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityManagerPaymentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function setupBooking(): array
    {
        config(['filesystems.facility_billing_disk' => 'local']); Storage::fake('local'); Mail::fake();
        $manager = User::factory()->create(); $manager->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $verifier = User::factory()->create(); $verifier->assignRole('facility.payment-verifier');
        $this->actingAs($manager);
        Storage::disk('local')->put('facility-billing/soa/manager.pdf', 'SOA');
        $booking = ResourceReservation::create(['title' => 'Manager proof QA', 'status' => 'approved', 'billing_status' => 'billed',
            'requester_email' => 'requester@example.test', 'soa_path' => 'facility-billing/soa/manager.pdf', 'soa_sent_at' => today(),
            'start_datetime' => now()->subDays(2), 'end_datetime' => now()->subDay()]);
        return [$manager, $booking, $verifier];
    }

    private function data(): array
    {
        return ['paid_on' => today()->toDateString(), 'reference' => 'MANAGER-REF', 'submission_note' => 'Receipt received by email from organizer.',
            'proofs' => [UploadedFile::fake()->create('receipt.pdf', 2, 'application/pdf')]];
    }

    public function test_manager_upload_stays_billed_until_finance_confirms_and_receipt_is_not_sent_early(): void
    {
        [$manager, $booking, $verifier] = $this->setupBooking();
        Livewire::test(ReservationIndex::class)->call('selectForPayment', $booking->id)
            ->set('paymentReference', 'MANAGER-REF')->set('paymentSubmissionNote', 'Received by email')
            ->set('paymentProofs', $this->data()['proofs'])->call('submitPaymentProof')->assertHasNoErrors()->assertSet('approvedFilter', 'billed');
        $submission = $booking->paymentSubmissions()->firstOrFail();
        $this->assertSame('pending', $submission->status); $this->assertSame($manager->id, $submission->submitted_by);
        $this->assertNull($submission->access_link_id); $this->assertSame('billed', $booking->fresh()->billing_status);
        $this->assertNull($booking->fresh()->paid_at); $this->assertFalse($booking->fresh()->is_archived);
        $this->assertSame(0, $booking->paymentProofs()->count());
        Mail::assertQueued(\App\Mail\FacilityPaymentReviewMail::class, fn($m) => $m->hasTo($verifier->email));
        Mail::assertNotQueued(\App\Mail\FacilityPaymentReceived::class);
        $this->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed', 'confirm_full_payment' => 1])->assertForbidden();
        $this->actingAs($verifier)->get(route('facilities.payments.review', $booking))->assertOk()->assertSee('Received by email')->assertSee($manager->name);
        $this->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed'])->assertSessionHasErrors('confirm_full_payment');
        $this->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed', 'confirm_full_payment' => 1])->assertSessionHasNoErrors();
        $this->assertSame('paid', $booking->fresh()->billing_status); $this->assertTrue($booking->fresh()->is_archived);
        $this->assertSame($verifier->id, $submission->fresh()->reviewed_by); Mail::assertQueued(\App\Mail\FacilityPaymentReceived::class, 1);
    }

    public function test_legacy_record_payment_action_requires_note_reference_and_files_and_cannot_mark_paid(): void
    {
        [, $booking] = $this->setupBooking();
        $component = Livewire::test(ReservationIndex::class)->call('selectForPayment', $booking->id);
        $component->call('recordPayment')->assertHasErrors(['paymentReference', 'paymentSubmissionNote']);
        $component->set('paymentReference', 'REF')->set('paymentSubmissionNote', 'Received receipt')->call('recordPayment')->assertHasErrors('paymentProofs');
        $component->set('paymentProofs', $this->data()['proofs'])->call('recordPayment')->assertHasNoErrors();
        $this->assertSame('billed', $booking->fresh()->billing_status); Mail::assertNotQueued(\App\Mail\FacilityPaymentReceived::class);
    }

    public function test_pending_requester_proof_cannot_be_superseded_by_manager_upload(): void
    {
        [$manager, $booking] = $this->setupBooking(); $service = app(Payments::class);
        $old = $service->submit(basename($service->link($booking)), $this->data());
        Livewire::test(ReservationIndex::class)->call('selectForPayment', $booking->id)->set('paymentReference', 'ALT')
            ->set('paymentSubmissionNote', 'Alternate receipt')->set('paymentProofs', $this->data()['proofs'])->call('submitPaymentProof')->assertHasErrors('paymentProofs');
        $this->assertSame('pending', $old->fresh()->status); $this->assertNull($old->fresh()->reviewed_by);
        $this->assertSame(1, $booking->paymentSubmissions()->count()); $this->assertSame(0, $booking->paymentOverrides()->count());
        Storage::disk('local')->assertExists($old->files[0]['path']);
    }

    public function test_paid_correction_reopens_billed_and_finance_alone_replaces_confirmed_evidence(): void
    {
        [$manager, $booking, $verifier] = $this->setupBooking();
        Storage::disk('local')->put('facility-billing/payments/original.pdf', 'original');
        $old = $booking->paymentProofs()->create(['path' => 'facility-billing/payments/original.pdf', 'original_name' => 'original.pdf', 'paid_on' => today()->subDay()]);
        $booking->update(['billing_status' => 'paid', 'paid_at' => today()->subDay(), 'payment_proof_path' => $old->path]);
        $submission = app(Payments::class)->submitManager($booking->id, $manager, $this->data());
        $this->assertSame('billed', $booking->fresh()->billing_status); $this->assertFalse($booking->fresh()->is_archived);
        $this->assertSame([$old->id], $submission->previous_proof_ids); $this->assertNull($old->fresh()->superseded_at);
        $this->assertSame($old->path, $booking->fresh()->payment_proof_path); Mail::assertNotQueued(\App\Mail\FacilityPaymentReceived::class);
        app(Payments::class)->review($submission->id, $verifier, 'confirmed', 'Checked bank receipt');
        $this->assertSame('paid', $booking->fresh()->billing_status); $this->assertNotNull($old->fresh()->superseded_at);
        $this->assertSame($verifier->id, $old->fresh()->superseded_by); $this->assertNotSame($old->path, $booking->fresh()->payment_proof_path);
        Storage::disk('local')->assertExists($old->path); Mail::assertNotQueued(\App\Mail\FacilityPaymentReceived::class);
    }

    public function test_finance_correction_then_manager_resubmission_retains_all_history(): void
    {
        [$manager, $booking, $verifier] = $this->setupBooking(); $service = app(Payments::class);
        $old = $service->submitManager($booking->id, $manager, $this->data());
        $service->review($old->id, $verifier, 'correction', 'Please send clearer receipt.');
        $new = $service->submitManager($booking->id, $manager, $this->data());
        $this->assertSame('correction', $old->fresh()->status); $this->assertSame('pending', $new->status);
        $this->assertSame(2, $booking->paymentSubmissions()->count()); $this->assertNull($booking->fresh()->paid_at);
        Storage::disk('local')->assertExists($old->files[0]['path']);
    }

    public function test_non_manager_cannot_submit(): void
    {
        [, $booking, $verifier] = $this->setupBooking();
        Livewire::actingAs($verifier)->test(ReservationIndex::class)->assertForbidden();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(Payments::class)->submitManager($booking->id, $verifier, $this->data());
    }

    public function test_invalid_date_note_and_file_leave_no_payment_or_submission(): void
    {
        [$manager, $booking] = $this->setupBooking();
        Livewire::test(ReservationIndex::class)->call('selectForPayment', $booking->id)
            ->set('paymentReference', 'REF')->set('paymentSubmissionNote', '   ')->set('paymentDate', today()->addDay()->toDateString())
            ->set('paymentProofs', [UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->call('submitPaymentProof')
            ->assertHasErrors(['paymentSubmissionNote', 'paymentDate', 'paymentProofs.0']);
        $this->assertSame(0, $booking->paymentSubmissions()->count()); $this->assertNull($booking->fresh()->paid_at);
    }

    public function test_requester_resubmission_retains_paid_correction_history_until_finance_confirms(): void
    {
        [$manager, $booking, $verifier] = $this->setupBooking();
        Storage::disk('local')->put('facility-billing/payments/original.pdf', 'original');
        $old = $booking->paymentProofs()->create(['path' => 'facility-billing/payments/original.pdf', 'original_name' => 'original.pdf', 'paid_on' => today()->subDay()]);
        $booking->update(['billing_status' => 'paid', 'paid_at' => today()->subDay(), 'payment_proof_path' => $old->path]);
        $service = app(Payments::class);
        $managerSubmission = $service->submitManager($booking->id, $manager, $this->data());
        $service->review($managerSubmission->id, $verifier, 'correction', 'Please provide the clear receipt.');
        $requesterSubmission = $service->submit(basename($service->link($booking->fresh())), $this->data());
        $this->assertSame([$old->id], $requesterSubmission->previous_proof_ids);
        $this->assertNull($old->fresh()->superseded_at);
        $this->assertSame('billed', $booking->fresh()->billing_status);
        $service->review($requesterSubmission->id, $verifier, 'confirmed', 'Verified replacement.');
        $this->assertSame('paid', $booking->fresh()->billing_status);
        $this->assertNotNull($old->fresh()->superseded_at);
        Storage::disk('local')->assertExists($old->path);
        Storage::disk('local')->assertExists($managerSubmission->files[0]['path']);
        Mail::assertNotQueued(\App\Mail\FacilityPaymentReceived::class);
    }

    public function test_pending_manager_proof_is_billed_even_before_soa_email_and_reminders_are_blocked(): void
    {
        [$manager, $booking] = $this->setupBooking();
        $booking->update(['billing_status' => 'unbilled', 'soa_sent_at' => null, 'soa_email_pending' => true]);
        app(Payments::class)->submitManager($booking->id, $manager, $this->data());
        $this->assertSame('billed', $booking->fresh()->billing_filter); $this->assertTrue($booking->fresh()->soa_email_pending);
        $this->assertFalse(ResourceReservation::awaitingBilling()->whereKey($booking->id)->exists());
    }
}
