<?php

namespace Tests\Feature;

use App\Jobs\SendClinicConsultationEmail;
use App\Mail\StudentGoHomeClinicMail;
use App\Mail\StudentResumeClassClinicMail;
use App\Models\ClinicConsultation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClinicConsultationMailRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mail.clinic_recipients', [
            'clinic' => 'clinic@example.com',
            'academic_council' => 'academic-council@example.com',
            'sas' => 'sas@example.com',
        ]);
    }

    protected function clinicUser(): User
    {
        Role::findOrCreate('clinic.admin', 'web');

        $user = User::factory()->create();
        $user->assignRole('clinic.admin');

        return $user;
    }

    protected function student(bool $underAccessibility): Patient
    {
        return Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'email' => 'ana.student@example.com',
            'type' => 'student',
            'is_under_accessibility' => $underAccessibility,
        ]);
    }

    protected function openConsultation(Patient $patient): ClinicConsultation
    {
        return ClinicConsultation::create([
            'patient_id' => $patient->id,
            'time_in' => now()->subMinutes(30),
            'check_in_teacher' => 'Check-in Teacher',
            'check_in_teacher_email' => 'checkin.teacher@example.com',
        ]);
    }

    public function test_regular_student_email_uses_selected_teachers_and_standard_cc_recipients(): void
    {
        Queue::fake();

        $patient = $this->student(false);
        $consultation = $this->openConsultation($patient);

        $this->actingAs($this->clinicUser())
            ->post(route('clinic.consultations.store', $patient), [
                'consultation_id' => $consultation->id,
                'current_teacher' => 'Checkout Teacher',
                'teacher_email' => 'checkout.teacher@example.com',
                'after_consultation' => 'resume',
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.message', 'Consultation submitted.');

        $consultation->refresh();

        $this->assertSame(ClinicConsultation::EMAIL_STATUS_QUEUED, $consultation->email_status);
        Queue::assertPushed(SendClinicConsultationEmail::class, function ($job) use ($consultation) {
            return $job->consultationId === $consultation->id && $job->queue === null;
        });

        $this->get(route('clinic.consultations.show', $consultation))
            ->assertOk()
            ->assertDontSeeText('EMAIL NOTIFICATION')
            ->assertDontSeeText('Queued')
            ->assertDontSeeText('Success')
            ->assertDontSeeText('No email was sent.');

        Mail::fake();
        (new SendClinicConsultationEmail($consultation->id))->handle();

        Mail::assertSent(StudentResumeClassClinicMail::class, function ($mail) {
            return $mail->hasTo('checkin.teacher@example.com')
                && $mail->hasTo('checkout.teacher@example.com')
                && $mail->hasCc('clinic@example.com')
                && $mail->hasCc('academic-council@example.com')
                && ! $mail->hasCc('sas@example.com')
                && ! $mail->hasBcc('ana.student@example.com');
        });

        $consultation->refresh();
        $this->assertSame(ClinicConsultation::EMAIL_STATUS_SENT, $consultation->email_status);
        $this->assertNotNull($consultation->email_sent_at);

        $this->get(route('clinic.consultations.show', $consultation))
            ->assertOk()
            ->assertSeeText('EMAIL NOTIFICATION')
            ->assertSeeText('Success')
            ->assertSeeText($consultation->email_sent_at->format('M d, Y h:i A'))
            ->assertDontSeeText('Queued')
            ->assertDontSeeText('Failed');
    }

    public function test_accessibility_student_email_adds_sas_and_never_bccs_patient(): void
    {
        Queue::fake();

        $patient = $this->student(true);
        $consultation = $this->openConsultation($patient);

        $this->actingAs($this->clinicUser())
            ->post(route('clinic.consultations.store', $patient), [
                'consultation_id' => $consultation->id,
                'current_teacher' => 'Checkout Teacher',
                'teacher_email' => 'checkout.teacher@example.com',
                'after_consultation' => 'go_home',
                'going_home_method' => 'self',
                'self_approved_by' => 'Clinic Administrator',
            ])
            ->assertRedirect();

        $consultation->refresh();
        $this->assertSame(ClinicConsultation::EMAIL_STATUS_QUEUED, $consultation->email_status);

        Mail::fake();
        (new SendClinicConsultationEmail($consultation->id))->handle();

        Mail::assertSent(StudentGoHomeClinicMail::class, function ($mail) {
            return $mail->hasTo('checkin.teacher@example.com')
                && $mail->hasTo('checkout.teacher@example.com')
                && $mail->hasCc('clinic@example.com')
                && $mail->hasCc('academic-council@example.com')
                && $mail->hasCc('sas@example.com')
                && ! $mail->hasBcc('ana.student@example.com');
        });

        $consultation->refresh();
        $this->assertSame(ClinicConsultation::EMAIL_STATUS_SENT, $consultation->email_status);
    }

    public function test_no_teacher_email_does_not_queue_or_send_notification(): void
    {
        Queue::fake();
        $patient = $this->student(false);
        $consultation = ClinicConsultation::create([
            'patient_id' => $patient->id,
            'time_in' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->clinicUser())
            ->post(route('clinic.consultations.store', $patient), [
                'consultation_id' => $consultation->id,
                'after_consultation' => 'resume',
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('flash.message', 'Consultation submitted.');

        Queue::assertNotPushed(SendClinicConsultationEmail::class);

        $consultation->refresh();
        $this->assertNull($consultation->email_status);

        Mail::fake();
        (new SendClinicConsultationEmail($consultation->id))->handle();
        Mail::assertNothingSent();

        $consultation->refresh();
        $this->assertNull($consultation->email_status);

        $this->get(route('clinic.consultations.show', $consultation))
            ->assertOk()
            ->assertSeeText('No email was sent.');
    }

    public function test_failed_delivery_marks_consultation_as_failed(): void
    {
        $consultation = $this->openConsultation($this->student(false));
        $consultation->update([
            'after_consultation' => 'resume',
            'email_status' => ClinicConsultation::EMAIL_STATUS_QUEUED,
        ]);

        (new SendClinicConsultationEmail($consultation->id))
            ->failed(new RuntimeException('SMTP rejected the message.'));

        $consultation->refresh();

        $this->assertSame(ClinicConsultation::EMAIL_STATUS_FAILED, $consultation->email_status);
        $this->assertNotNull($consultation->email_failed_at);
        $this->assertSame('SMTP rejected the message.', $consultation->email_failure_message);

        $this->actingAs($this->clinicUser())
            ->get(route('clinic.consultations.show', $consultation))
            ->assertOk()
            ->assertSeeText('Failed')
            ->assertSeeText('SMTP rejected the message.')
            ->assertSeeText('Retry email')
            ->assertDontSeeText('Success');
    }

    public function test_job_does_not_send_when_consultation_is_already_failed(): void
    {
        Mail::fake();

        $consultation = $this->openConsultation($this->student(false));
        $consultation->update([
            'after_consultation' => 'resume',
            'email_status' => ClinicConsultation::EMAIL_STATUS_FAILED,
            'email_failure_message' => 'Delivery canceled before processing.',
        ]);

        (new SendClinicConsultationEmail($consultation->id))->handle();

        Mail::assertNothingSent();
        $this->assertSame(
            ClinicConsultation::EMAIL_STATUS_FAILED,
            $consultation->fresh()->email_status,
        );
    }

    public function test_clinic_user_can_retry_a_failed_email(): void
    {
        Queue::fake();

        $consultation = $this->openConsultation($this->student(false));
        $consultation->update([
            'after_consultation' => 'resume',
            'email_status' => ClinicConsultation::EMAIL_STATUS_FAILED,
            'email_failed_at' => now(),
            'email_failure_message' => 'Previous failure.',
        ]);

        $this->actingAs($this->clinicUser())
            ->post(route('clinic.consultations.email.retry', $consultation))
            ->assertRedirect()
            ->assertSessionHas('flash.message', 'Email retry requested. Check the consultation details for the result.');

        $consultation->refresh();

        $this->assertSame(ClinicConsultation::EMAIL_STATUS_QUEUED, $consultation->email_status);
        $this->assertNull($consultation->email_failed_at);
        $this->assertNull($consultation->email_failure_message);
        Queue::assertPushed(SendClinicConsultationEmail::class, function ($job) use ($consultation) {
            return $job->consultationId === $consultation->id && $job->queue === null;
        });
    }

    public function test_consultation_details_display_check_in_and_check_out_teachers(): void
    {
        $consultation = $this->openConsultation($this->student(false));
        $consultation->update([
            'current_teacher' => 'Checkout Teacher',
            'teacher_email' => 'checkout.teacher@example.com',
            'after_consultation' => 'resume',
        ]);

        $this->actingAs($this->clinicUser())
            ->get(route('clinic.consultations.show', $consultation))
            ->assertOk()
            ->assertSeeText('CHECK-IN TEACHER')
            ->assertSeeText('Check-in Teacher')
            ->assertSeeText('checkin.teacher@example.com')
            ->assertSeeText('CHECK-OUT TEACHER')
            ->assertSeeText('Checkout Teacher')
            ->assertSeeText('checkout.teacher@example.com');
    }

    public function test_failure_description_is_escaped_in_consultation_details(): void
    {
        $failure = 'SMTP rejected <script>alert("test")</script>';
        $consultation = $this->openConsultation($this->student(false));
        $consultation->update([
            'after_consultation' => 'resume',
            'email_status' => ClinicConsultation::EMAIL_STATUS_FAILED,
            'email_failure_message' => $failure,
        ]);

        $this->actingAs($this->clinicUser())
            ->get(route('clinic.consultations.show', $consultation))
            ->assertOk()
            ->assertSee($failure)
            ->assertDontSee($failure, false);
    }

    public function test_failed_email_without_recorded_error_has_a_description(): void
    {
        $consultation = $this->openConsultation($this->student(false));
        $consultation->update([
            'after_consultation' => 'resume',
            'email_status' => ClinicConsultation::EMAIL_STATUS_FAILED,
        ]);

        $this->actingAs($this->clinicUser())
            ->get(route('clinic.consultations.show', $consultation))
            ->assertOk()
            ->assertSeeText('Failed')
            ->assertSeeText('The email could not be sent. No error description was recorded.');
    }
}
