<?php

namespace Tests\Feature;

use App\Mail\{FacilityBillingMail, FacilityPaymentReceived, FacilityPaymentReviewMail, FacilityReservationCancellationMail, ResourceBookingApproved, ResourceBookingEdited};
use App\Models\ResourceReservation;
use App\Services\{FacilityPaymentVerificationService, FacilityRequesterEmailActions};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Mail, Storage};
use Tests\TestCase;

class FacilityRequesterEmailActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.facility_billing_disk' => 'local']);
        Storage::fake('local'); Mail::fake();
    }

    private function booking(): ResourceReservation
    {
        return ResourceReservation::create(['title' => 'Requester actions event', 'requester_email' => 'requester@example.test',
            'status' => 'approved', 'start_datetime' => now()->addWeek(), 'end_datetime' => now()->addWeek()->addHour()]);
    }

    private function details(ResourceReservation $reservation): array
    {
        return ['title' => $reservation->title, 'reservation_id' => $reservation->id, 'event_date' => 'Test schedule',
            'room' => 'Room A', 'due_date' => 'Nov 20, 2026', 'paid_date' => 'Nov 3, 2026', 'paid' => false,
            'recipient' => $reservation->requester_email, 'disk' => 'local', 'attachment' => 'test.pdf'];
    }

    public function test_approval_and_update_show_cancel_but_no_payment_button_before_soa(): void
    {
        $reservation = $this->booking();
        foreach ([new ResourceBookingApproved($reservation), new ResourceBookingEdited($reservation, [])] as $mail) {
            $html = $mail->to($reservation->requester_email)->render();
            $this->assertStringContainsString('View / Cancel reservation', $html);
            $this->assertStringNotContainsString('Upload proof of payment', $html);
        }
    }

    public function test_all_eligible_requester_emails_have_both_buttons_after_soa_upload(): void
    {
        $reservation = $this->booking(); $reservation->update(['soa_path' => 'facility-billing/soa/test.pdf', 'billing_status' => 'billed']);
        $mailables = [new ResourceBookingApproved($reservation), new ResourceBookingEdited($reservation, []),
            new FacilityPaymentReviewMail('Correction requested', 'Readable receipt required.', '', '', $reservation->id)];
        foreach (['soa', 'updated_soa', 'upcoming', 'due_today', 'overdue'] as $kind) $mailables[] = new FacilityBillingMail($this->details($reservation), $kind);
        foreach ($mailables as $mail) {
            $html = $mail->to($reservation->requester_email)->render();
            $this->assertStringContainsString('View / Cancel reservation', $html);
            $this->assertStringContainsString('Upload proof of payment', $html);
            $this->assertStringNotContainsString('/storage/facility-billing/', $html);
        }
    }

    public function test_pending_verification_and_paid_receipt_do_not_offer_more_uploads(): void
    {
        $reservation = $this->booking(); $reservation->update(['soa_path' => 'facility-billing/soa/test.pdf', 'billing_status' => 'billed']);
        $token = basename(app(FacilityPaymentVerificationService::class)->link($reservation));
        app(FacilityPaymentVerificationService::class)->submit($token, ['paid_on' => today()->toDateString(), 'reference' => 'BANK-TEST',
            'proofs' => [UploadedFile::fake()->create('proof.pdf', 1, 'application/pdf')]]);
        $html = (new ResourceBookingEdited($reservation->fresh(), []))->to($reservation->requester_email)->render();
        $this->assertStringContainsString('Payment verification pending', $html);
        $this->assertStringNotContainsString('Upload proof of payment', $html);
        $reservation->update(['billing_status' => 'paid', 'paid_at' => now()]);
        $html = (new FacilityPaymentReceived($this->details($reservation)))->to($reservation->requester_email)->render();
        $this->assertStringContainsString('View / Cancel reservation', $html);
        $this->assertStringNotContainsString('Upload proof of payment', $html);
        $this->assertStringNotContainsString('Payment verification pending', $html);
    }

    public function test_internal_forwarded_or_old_recipient_emails_cannot_receive_requester_magic_links(): void
    {
        $reservation = $this->booking(); $reservation->update(['soa_path' => 'facility-billing/soa/test.pdf']);
        $internal = (new FacilityPaymentReviewMail('Review payment', 'Finance alert', 'http://localhost/review', 'Review payment'))->to('finance@example.test')->render();
        $this->assertStringNotContainsString('View / Cancel reservation', $internal);
        $this->assertStringNotContainsString('Upload proof of payment', $internal);
        foreach ([(new ResourceBookingApproved($reservation))->to('someone-else@example.test'),
            (new ResourceBookingApproved($reservation))->to($reservation->requester_email)->cc('finance@example.test')] as $mail) {
            $html = $mail->render();
            $this->assertStringNotContainsString('/resources/manage/', $html);
            $this->assertStringNotContainsString('/resources/payment/', $html);
        }
        $oldMail = (new ResourceBookingApproved($reservation))->to($reservation->requester_email);
        $reservation->update(['requester_email' => 'new@example.test']);
        $this->assertStringNotContainsString('/resources/manage/', $oldMail->render());
    }

    public function test_rejected_deleted_and_finished_events_never_offer_cancellation(): void
    {
        $reservation = $this->booking(); $reservation->update(['status' => 'rejected']);
        $this->assertSame([], app(FacilityRequesterEmailActions::class)->forReservation($reservation, $reservation->requester_email));
        $reservation->update(['status' => 'approved', 'start_datetime' => now()->subHours(2), 'end_datetime' => now()->subHour()]);
        $html = (new ResourceBookingEdited($reservation, []))->to($reservation->requester_email)->render();
        $this->assertStringContainsString('View reservation', $html);
        $this->assertStringNotContainsString('View / Cancel reservation', $html);
        $reservation->delete();
        $this->assertSame([], app(FacilityRequesterEmailActions::class)->forReservation($reservation, $reservation->requester_email));
    }

    public function test_cancelled_confirmation_has_no_actions_and_internal_cancellation_alert_has_no_private_links(): void
    {
        $reservation = $this->booking();
        $details = ['title' => $reservation->title, 'id' => $reservation->id, 'schedule' => 'Future date', 'rooms' => 'Room A', 'reason' => 'Test', 'status' => 'cancelled'];
        $html = (new FacilityReservationCancellationMail($details))->to($reservation->requester_email)->render();
        $this->assertStringNotContainsString('/resources/manage/', $html);
        $details['status'] = 'declined';
        $html = (new FacilityReservationCancellationMail($details))->to('finance@example.test')->render();
        $this->assertStringNotContainsString('/resources/manage/', $html);
        $html = (new FacilityReservationCancellationMail($details))->to($reservation->requester_email)->render();
        $this->assertStringContainsString('View / Cancel reservation', $html);
    }
}
