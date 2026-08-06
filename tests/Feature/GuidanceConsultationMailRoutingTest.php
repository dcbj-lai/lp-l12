<?php

namespace Tests\Feature;

use App\Jobs\SendGuidanceConsultationEmail;
use App\Mail\StudentGoHomeMail;
use App\Mail\StudentResumeClassMail;
use App\Models\Client;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuidanceConsultationMailRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mail.guidance_recipients', [
            'guidance' => 'guidance@example.com',
            'academic_council' => 'academic-council@example.com',
            'sas' => 'sas@example.com',
        ]);
    }

    protected function guidanceUser(): User
    {
        Role::findOrCreate('guidance.admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('guidance.admin');

        return $user;
    }

    protected function student(bool $underAccessibility): Client
    {
        return Client::create([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'email' => 'ana.guidance.mail@example.com',
            'is_under_accessibility' => $underAccessibility,
        ]);
    }

    protected function openConsultation(Client $client): Consultation
    {
        return Consultation::create([
            'client_id' => $client->id,
            'time_in' => now()->subMinutes(30),
            'check_in_teacher' => 'Check-in Teacher',
            'check_in_teacher_email' => 'checkin.guidance.teacher@example.com',
        ]);
    }

    public function test_regular_student_email_uses_selected_teachers_and_standard_cc_recipients(): void
    {
        Queue::fake();
        $client = $this->student(false);
        $consultation = $this->openConsultation($client);

        $response = $this->actingAs($this->guidanceUser())
            ->post(route('guidance.consultations.store', $client), [
                'current_teacher' => 'Check-out Teacher',
                'teacher_email' => 'checkout.guidance.teacher@example.com',
                'after_consultation' => 'resume',
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Consultation submitted. Email notification queued.');

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSeeText('Consultation submitted. Email notification queued.');

        $consultation->refresh();
        $this->assertSame(Consultation::EMAIL_STATUS_QUEUED, $consultation->email_status);
        Queue::assertPushed(SendGuidanceConsultationEmail::class, function ($job) use ($consultation) {
            return $job->consultationId === $consultation->id && $job->queue === 'mail';
        });

        Mail::fake();
        (new SendGuidanceConsultationEmail($consultation->id))->handle();

        Mail::assertSent(StudentResumeClassMail::class, function ($mail) {
            return $mail->hasTo('checkin.guidance.teacher@example.com')
                && $mail->hasTo('checkout.guidance.teacher@example.com')
                && $mail->hasCc('guidance@example.com')
                && $mail->hasCc('academic-council@example.com')
                && ! $mail->hasCc('sas@example.com')
                && ! $mail->hasBcc('ana.guidance.mail@example.com');
        });

        $consultation->refresh();
        $this->assertSame(Consultation::EMAIL_STATUS_SENT, $consultation->email_status);
        $this->assertNotNull($consultation->email_sent_at);
    }

    public function test_accessibility_student_adds_sas_and_never_bccs_client(): void
    {
        Queue::fake();
        $client = $this->student(true);
        $consultation = $this->openConsultation($client);

        $this->actingAs($this->guidanceUser())
            ->post(route('guidance.consultations.store', $client), [
                'current_teacher' => 'Check-out Teacher',
                'teacher_email' => 'checkout.guidance.teacher@example.com',
                'after_consultation' => 'go_home',
                'going_home_method' => 'self',
                'self_approved_by' => 'Guidance Administrator',
            ])
            ->assertRedirect();

        Mail::fake();
        (new SendGuidanceConsultationEmail($consultation->id))->handle();

        Mail::assertSent(StudentGoHomeMail::class, function ($mail) {
            return $mail->hasTo('checkin.guidance.teacher@example.com')
                && $mail->hasTo('checkout.guidance.teacher@example.com')
                && $mail->hasCc('guidance@example.com')
                && $mail->hasCc('academic-council@example.com')
                && $mail->hasCc('sas@example.com')
                && ! $mail->hasBcc('ana.guidance.mail@example.com');
        });
    }

    public function test_failed_delivery_marks_consultation_as_failed(): void
    {
        $consultation = $this->openConsultation($this->student(false));
        $consultation->update([
            'after_consultation' => 'resume',
            'email_status' => Consultation::EMAIL_STATUS_QUEUED,
        ]);

        (new SendGuidanceConsultationEmail($consultation->id))
            ->failed(new RuntimeException('SMTP rejected the Guidance message.'));

        $consultation->refresh();
        $this->assertSame(Consultation::EMAIL_STATUS_FAILED, $consultation->email_status);
        $this->assertNotNull($consultation->email_failed_at);
        $this->assertSame('SMTP rejected the Guidance message.', $consultation->email_failure_message);
    }

    public function test_guidance_user_can_retry_failed_email(): void
    {
        Queue::fake();
        $consultation = $this->openConsultation($this->student(false));
        $consultation->update([
            'after_consultation' => 'resume',
            'email_status' => Consultation::EMAIL_STATUS_FAILED,
            'email_failed_at' => now(),
            'email_failure_message' => 'Previous failure.',
        ]);

        $this->actingAs($this->guidanceUser())
            ->post(route('guidance.consultations.email.retry', $consultation))
            ->assertRedirect()
            ->assertSessionHas('success', 'Email notification queued for retry.');

        $consultation->refresh();
        $this->assertSame(Consultation::EMAIL_STATUS_QUEUED, $consultation->email_status);
        $this->assertNull($consultation->email_failed_at);
        $this->assertNull($consultation->email_failure_message);
        Queue::assertPushed(SendGuidanceConsultationEmail::class, fn ($job) => $job->consultationId === $consultation->id);
    }


    public function test_consultation_details_display_teachers_and_email_status(): void
    {
        $consultation = $this->openConsultation($this->student(false));
        $consultation->update([
            'time_out' => now(),
            'current_teacher' => 'Check-out Teacher',
            'teacher_email' => 'checkout.guidance.teacher@example.com',
            'after_consultation' => 'resume',
            'email_status' => Consultation::EMAIL_STATUS_FAILED,
            'email_failure_message' => 'Test failure.',
        ]);

        $this->actingAs($this->guidanceUser())
            ->get(route('guidance.consultations.show', $consultation))
            ->assertOk()
            ->assertSeeText('CHECK-IN TEACHER')
            ->assertSeeText('checkin.guidance.teacher@example.com')
            ->assertSeeText('CHECK-OUT TEACHER')
            ->assertSeeText('checkout.guidance.teacher@example.com')
            ->assertSeeText('EMAIL NOTIFICATION')
            ->assertSeeText('Failed')
            ->assertSeeText('Retry email');
    }
}
