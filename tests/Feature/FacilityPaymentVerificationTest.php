<?php

namespace Tests\Feature;

use App\Models\{FacilityPaymentLink, FacilityPaymentSubmission, ResourceReservation, User};
use App\Services\FacilityPaymentVerificationService as Payments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Mail, Storage};
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityPaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.facility_billing_disk' => 'local']);
        Storage::fake('local');
        Mail::fake();
    }

    private function booking(): ResourceReservation
    {
        Storage::disk('local')->put('facility-billing/soa/example.pdf', 'test SOA');
        return ResourceReservation::create(['title' => 'Payment verification test', 'requester_email' => 'requester@example.test',
            'status' => 'approved', 'billing_status' => 'billed', 'soa_path' => 'facility-billing/soa/example.pdf',
            'start_datetime' => now()->subDays(2), 'end_datetime' => now()->subDay(), 'payment_due_at' => today()->addDays(15)]);
    }

    private function verifier(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate(Payments::ROLE, 'web'));
        return $user;
    }

    private function token(ResourceReservation $reservation): string
    {
        return basename(app(Payments::class)->link($reservation));
    }

    private function data(): array
    {
        return ['paid_on' => today()->toDateString(), 'reference' => 'BANK-123', 'full_payment' => '1',
            'proofs' => [UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')]];
    }

    public function test_guest_upload_stays_unpaid_and_finance_confirmation_preserves_files(): void
    {
        $reservation = $this->booking(); $verifier = $this->verifier();
        $token = $this->token($reservation);
        $this->get(route('facilities.payment.show', $token))->assertOk()->assertSee($reservation->title);
        $this->post(route('facilities.payment.upload', $token), $this->data())->assertSessionHasNoErrors()->assertRedirect();
        $submission = FacilityPaymentSubmission::firstOrFail();
        $this->assertSame('billed', $reservation->fresh()->billing_status);
        $this->assertNull($reservation->fresh()->paid_at);
        $this->assertSame(0, $reservation->paymentProofs()->count());
        Mail::assertQueued(\App\Mail\FacilityPaymentReviewMail::class);
        $this->actingAs($verifier)->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed', 'confirm_full_payment' => '1'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('paid', $reservation->fresh()->billing_status);
        $this->assertTrue($reservation->fresh()->is_archived);
        $this->assertSame(1, $reservation->paymentProofs()->count());
        $this->assertSame($verifier->id, $submission->fresh()->reviewed_by);
        Storage::disk('local')->assertExists($submission->files[0]['path']);
        Mail::assertQueued(\App\Mail\FacilityPaymentReceived::class, 1);
        $this->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed', 'confirm_full_payment' => '1'])->assertSessionHasErrors('decision');
        $this->assertSame(1, $reservation->paymentProofs()->count());
        Mail::assertQueued(\App\Mail\FacilityPaymentReceived::class, 1);
    }

    public function test_duplicate_upload_is_blocked_and_original_submission_is_retained(): void
    {
        $reservation = $this->booking(); $token = $this->token($reservation);
        $this->post(route('facilities.payment.upload', $token), $this->data());
        $this->post(route('facilities.payment.upload', $token), $this->data())->assertSessionHasErrors('proofs');
        $this->assertSame(1, FacilityPaymentSubmission::count());
        $this->assertCount(1, Storage::disk('local')->allFiles('facility-billing/payment-submissions'));
    }

    public function test_correction_requires_note_and_allows_another_submission_without_losing_evidence(): void
    {
        $reservation = $this->booking(); $token = $this->token($reservation); $verifier = $this->verifier();
        $submission = app(Payments::class)->submit($token, $this->data());
        $this->actingAs($verifier)->post(route('facilities.payments.decide', $submission), ['decision' => 'correction'])->assertSessionHasErrors('review_note');
        $this->post(route('facilities.payments.decide', $submission), ['decision' => 'correction', 'review_note' => 'Please upload a readable copy.'])->assertSessionHasNoErrors();
        $this->assertSame('correction', $submission->fresh()->status);
        $second = app(Payments::class)->submit($token, $this->data());
        $this->assertNotSame($second->id, $submission->id);
        Storage::disk('local')->assertExists($submission->files[0]['path']);
        $this->assertSame('billed', $reservation->fresh()->billing_status);
    }

    public function test_all_role_holders_can_view_and_confirm_even_with_a_legacy_person_assignment(): void
    {
        $reservation = $this->booking(); $assigned = $this->verifier(); $other = $this->verifier();
        $reservation->update(['payment_verifier_id' => $assigned->id]);
        $submission = app(Payments::class)->submit($this->token($reservation), $this->data());
        $url = route('facilities.payments.file', [$submission, 0]);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($other)->get($url)->assertOk();
        $this->get(route('facilities.payments.index'))->assertSee($reservation->title);
        $this->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed', 'confirm_full_payment' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $reservation->fresh()->billing_status);
        $this->assertSame($other->id, $submission->fresh()->reviewed_by);
        $this->actingAs($assigned)->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $viewer = User::factory()->create(); $viewer->assignRole(Role::findOrCreate('facility.viewer', 'web'));
        $this->actingAs($viewer)->get($url)->assertForbidden();
        $admin = User::factory()->create(); $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $this->actingAs($admin)->get($url)->assertOk();
        $this->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed', 'confirm_full_payment' => '1'])->assertForbidden();
    }

    public function test_replacing_soa_invalidates_links_and_pending_proof_but_keeps_history(): void
    {
        $reservation = $this->booking(); $token = $this->token($reservation);
        $submission = app(Payments::class)->submit($token, $this->data());
        $reservation->update(['soa_path' => 'facility-billing/soa/replaced.pdf']);
        $this->get(route('facilities.payment.show', $token))->assertNotFound();
        $this->assertSame('superseded', $submission->fresh()->status);
        Storage::disk('local')->assertExists($submission->files[0]['path']);
        $this->actingAs($this->verifier())->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed', 'confirm_full_payment' => '1'])->assertSessionHasErrors();
        $this->assertSame('billed', $reservation->fresh()->billing_status);
    }

    public function test_expired_links_recipient_changes_and_deleted_reservations_are_rejected(): void
    {
        $reservation = $this->booking(); $token = $this->token($reservation);
        FacilityPaymentLink::query()->update(['expires_at' => now()->subMinute()]);
        $this->get(route('facilities.payment.show', $token))->assertNotFound();
        $token = $this->token($reservation); $reservation->update(['requester_email' => 'new@example.test']);
        $this->get(route('facilities.payment.show', $token))->assertNotFound();
        $token = $this->token($reservation); $reservation->delete();
        $this->post(route('facilities.payment.upload', $token), $this->data())->assertNotFound();
    }

    public function test_invalid_dates_file_types_and_missing_full_payment_confirmation_are_rejected(): void
    {
        $token = $this->token($this->booking());
        $this->post(route('facilities.payment.upload', $token), ['paid_on' => today()->addDay()->toDateString(), 'reference' => '',
            'proofs' => [UploadedFile::fake()->create('script.exe', 10)]])->assertSessionHasErrors(['paid_on', 'reference', 'proofs.0', 'full_payment']);
        $this->assertSame(0, FacilityPaymentSubmission::count());
    }

    public function test_payment_link_is_limited_to_one_event_and_paid_booking_cannot_be_submitted(): void
    {
        $reservation = $this->booking(); $token = $this->token($reservation);
        $other = $this->booking(); $other->update(['title' => 'Other private event']);
        $this->get(route('facilities.payment.show', $token))->assertDontSee('Other private event');
        $reservation->update(['billing_status' => 'paid', 'paid_at' => now()]);
        $this->post(route('facilities.payment.upload', $token), $this->data())->assertSessionHasErrors('proofs');
    }

    public function test_confirmation_requires_acknowledgement_and_revoking_the_role_removes_access(): void
    {
        $reservation = $this->booking(); $old = $this->verifier(); $new = $this->verifier();
        $reservation->update(['payment_verifier_id' => $old->id]);
        $submission = app(Payments::class)->submit($this->token($reservation), $this->data());
        $this->actingAs($old)->post(route('facilities.payments.decide', $submission), ['decision' => 'confirmed'])->assertSessionHasErrors('confirm_full_payment');
        $this->actingAs($new)->get(route('facilities.payments.review', $reservation))->assertOk()->assertDontSee('Save verifier');
        $old->removeRole(Payments::ROLE);
        $this->actingAs($old)->get(route('facilities.payments.review', $reservation))->assertForbidden();
        $this->actingAs($new)->get(route('facilities.payments.review', $reservation))->assertOk();
        $this->assertSame('billed', $reservation->fresh()->billing_status);
    }

    public function test_all_active_verifiers_are_notified_and_inactive_or_unrelated_users_are_excluded(): void
    {
        $reservation = $this->booking(); $first = $this->verifier(); $second = $this->verifier(); $inactive = $this->verifier();
        $inactive->update(['is_active' => false]);
        $reservation->update(['payment_verifier_id' => $first->id]);
        app(Payments::class)->submit($this->token($reservation), $this->data());
        Mail::assertQueued(\App\Mail\FacilityPaymentReviewMail::class, 2);
        foreach ([$first, $second] as $verifier) Mail::assertQueued(\App\Mail\FacilityPaymentReviewMail::class, fn ($mail) => $mail->hasTo($verifier->email));
        Mail::assertNotQueued(\App\Mail\FacilityPaymentReviewMail::class, fn ($mail) => $mail->hasTo($inactive->email));
        $this->actingAs($inactive)->get(route('facilities.payments.review', $reservation))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('facilities.payments.review', $reservation))->assertForbidden();
    }

    public function test_guest_soa_download_is_private_and_bad_token_cannot_access_it(): void
    {
        $token = $this->token($this->booking());
        $this->get(route('facilities.payment.soa', $token))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('facilities.payment.soa', str_repeat('a', 64)))->assertNotFound();
    }

    public function test_billing_email_contains_upload_link_without_retaining_raw_token_in_snapshot(): void
    {
        $reservation = $this->booking(); $reservation->update(['soa_email_pending' => true]);
        $admin = User::factory()->create(); $admin->assignRole(Role::findOrCreate('facility.admin', 'web'));
        $this->actingAs($admin);
        \Illuminate\Support\Facades\Queue::fake();
        app(\App\Services\FacilityBillingEmailService::class)->queue($reservation->id, 'soa');
        $message = \App\Models\FacilityBillingEmail::firstOrFail();
        (new \App\Jobs\SendFacilityBillingEmail($message->id))->handle();
        Mail::assertSent(\App\Mail\FacilityBillingMail::class, fn ($mail) => str_contains($mail->render(), 'Upload proof of payment'));
        $this->assertArrayNotHasKey('payment_url', $message->fresh()->snapshot);
        $this->assertSame(1, FacilityPaymentLink::count());
        $submission = app(Payments::class)->submit(basename(app(Payments::class)->link($reservation->fresh())), $this->data());
        try { app(\App\Services\FacilityBillingEmailService::class)->queue($reservation->id, 'reminder'); $this->fail('Reminder queued during review'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('awaiting finance', $e->getMessage()); }
    }
}
