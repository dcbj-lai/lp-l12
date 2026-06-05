<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicPatientCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function clinicUser(): User
    {
        return User::factory()->create([
            'legacy_roles' => ['user', 'clinic.admin'],
        ]);
    }

    public function test_clinic_user_can_view_create_student_patient_form(): void
    {
        $this->actingAs($this->clinicUser())
            ->get(route('clinic.patients.create', ['tab' => 'students']))
            ->assertOk()
            ->assertSee('Create Student Patient')
            ->assertSee('Student Information');
    }

    public function test_non_clinic_user_cannot_view_create_student_patient_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('clinic.patients.create', ['tab' => 'students']))
            ->assertForbidden();
    }

    public function test_clinic_user_can_create_student_patient_record(): void
    {
        $response = $this->actingAs($this->clinicUser())
            ->post(route('clinic.patients.store'), [
                'first_name' => 'Ana',
                'last_name' => 'Santos',
                'email' => 'ana.santos@example.com',
                'course' => 'BSN',
                'blood_type' => 'O+',
                'emergency_contact_person' => 'Maria Santos',
                'emergency_contact_number' => '+639171234567',
                'department' => 'Should not be saved',
                'position' => 'Should not be saved',
            ]);

        $patient = Patient::where('email', 'ana.santos@example.com')->firstOrFail();

        $response->assertRedirect(route('clinic.patients.show', ['patient' => $patient, 'tab' => 'students']));

        $this->assertSame('student', $patient->type);
        $this->assertSame('BSN', $patient->course);
        $this->assertNull($patient->department);
        $this->assertNull($patient->position);
        $this->assertSame('O+', $patient->blood_type);
        $this->assertSame('Maria Santos', $patient->emergency_contact_person);
    }

    public function test_student_patient_email_must_be_unique(): void
    {
        Patient::create([
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'email' => 'student@example.com',
            'type' => 'student',
        ]);

        $this->actingAs($this->clinicUser())
            ->from(route('clinic.patients.create', ['tab' => 'students']))
            ->post(route('clinic.patients.store'), [
                'first_name' => 'New',
                'last_name' => 'Student',
                'email' => 'student@example.com',
                'course' => 'BSN',
            ])
            ->assertRedirect(route('clinic.patients.create', ['tab' => 'students']))
            ->assertSessionHasErrors('email');
    }
}
