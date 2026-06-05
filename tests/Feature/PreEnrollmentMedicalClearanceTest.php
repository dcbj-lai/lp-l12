<?php

namespace Tests\Feature;

use App\Models\PreEnrollmentMedicalClearance;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreEnrollmentMedicalClearanceTest extends TestCase
{
    use RefreshDatabase;

    protected function clinicUser(): User
    {
        return User::factory()->create([
            'name' => 'Clinic Admin Legal Name',
            'preferred_name' => 'Doc Preferred',
            'legacy_roles' => ['user', 'clinic.admin'],
        ]);
    }

    public function test_clinic_user_can_view_clearance_index_and_create_form(): void
    {
        Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'email' => 'ana.student@example.com',
            'type' => 'student',
            'course' => 'BS Nursing',
            'emergency_contact_number' => '+639171234567',
        ]);

        $this->actingAs($this->clinicUser())
            ->get(route('clinic.pre-enrollment-clearances.index'))
            ->assertOk()
            ->assertSee('Pre-enrollment Medical Clearances')
            ->assertSee('Issue Clearance');

        $this->actingAs($this->clinicUser())
            ->get(route('clinic.pre-enrollment-clearances.create'))
            ->assertOk()
            ->assertSee('Issue Pre-enrollment Medical Clearance')
            ->assertSee('Ana Santos')
            ->assertSee('Choose an existing student patient or type a new applicant name.')
            ->assertSee('Cleared for enrollment');
    }

    public function test_non_clinic_user_cannot_access_clearances(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('clinic.pre-enrollment-clearances.index'))
            ->assertForbidden();
    }

    public function test_clinic_user_can_create_pre_enrollment_clearance(): void
    {
        $admin = $this->clinicUser();

        $response = $this->actingAs($admin)
            ->post(route('clinic.pre-enrollment-clearances.store'), [
                'applicant_name' => 'Ana Santos',
                'email' => 'ana@example.com',
                'contact_number' => '+639171234567',
                'intended_course' => 'BS Nursing',
                'assessment_date' => '2026-06-05',
                'clearance_status' => PreEnrollmentMedicalClearance::STATUS_CLEARED,
                'findings' => 'No contraindications noted.',
                'recommendations' => 'Cleared for enrollment.',
            ]);

        $clearance = PreEnrollmentMedicalClearance::where('email', 'ana@example.com')->firstOrFail();

        $response->assertRedirect(route('clinic.pre-enrollment-clearances.show', $clearance));

        $this->assertSame('Ana Santos', $clearance->applicant_name);
        $this->assertSame('cleared', $clearance->clearance_status);
        $this->assertSame($admin->id, $clearance->issued_by_id);
        $this->assertSame('Clinic Admin Legal Name', $clearance->issued_by_name);
    }

    public function test_clearance_show_and_pdf_are_available(): void
    {
        $issuer = $this->clinicUser();

        $clearance = PreEnrollmentMedicalClearance::create([
            'applicant_name' => 'Ana Santos',
            'email' => 'ana@example.com',
            'intended_course' => 'BS Nursing',
            'assessment_date' => '2026-06-05',
            'clearance_status' => PreEnrollmentMedicalClearance::STATUS_PENDING,
            'findings' => 'Pending lab result.',
            'recommendations' => 'Submit complete blood count.',
            'issued_by_id' => $issuer->id,
            'issued_by_name' => 'Doc Preferred',
            'issued_at' => now(),
        ]);

        $this->assertSame('Clinic Admin Legal Name', $clearance->signatoryName());

        $this->actingAs($issuer)
            ->get(route('clinic.pre-enrollment-clearances.show', $clearance))
            ->assertOk()
            ->assertSee('Ana Santos')
            ->assertSee('Pending requirements')
            ->assertSee('Clinic Admin Legal Name')
            ->assertSee('Download PDF');

        $response = $this->actingAs($issuer)
            ->get(route('clinic.pre-enrollment-clearances.pdf', $clearance));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}
