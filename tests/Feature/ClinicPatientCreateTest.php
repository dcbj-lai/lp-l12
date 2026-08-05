<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ClinicConsultation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClinicPatientCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function clinicUser(): User
    {
        Role::findOrCreate('clinic.admin', 'web');

        $user = User::factory()->create();
        $user->assignRole('clinic.admin');

        return $user;
    }

    public function test_clinic_user_can_view_create_student_patient_form(): void
    {
        $this->actingAs($this->clinicUser())
            ->get(route('clinic.patients.create', ['tab' => 'students']))
            ->assertOk()
            ->assertSee('Create Student Patient')
            ->assertSee('Student Information')
            ->assertSee('name="type" value="student"', false)
            ->assertSee('Course')
            ->assertSee('name="is_under_accessibility"', false)
            ->assertDontSee('Select Department');
    }

    public function test_clinic_user_can_view_create_staff_patient_form_with_all_departments(): void
    {
        Department::create(['name' => 'Academics']);
        Department::create(['name' => 'Faculty']);

        $this->actingAs($this->clinicUser())
            ->get(route('clinic.patients.create', ['tab' => 'staff']))
            ->assertOk()
            ->assertSee('Create Staff Patient')
            ->assertSee('Staff Information')
            ->assertSee('name="type" value="staff"', false)
            ->assertSee('Select Department')
            ->assertSee('Academics')
            ->assertSee('Faculty')
            ->assertSee('Position')
            ->assertDontSee('name="is_under_accessibility"', false)
            ->assertDontSee('Course');
    }

    public function test_patient_index_has_create_actions_for_student_and_staff_tabs(): void
    {
        $response = $this->actingAs($this->clinicUser())
            ->get(route('clinic.patients.index'))
            ->assertOk()
            ->assertSee(route('clinic.patients.create', ['tab' => 'students']), false)
            ->assertSee(route('clinic.patients.create', ['tab' => 'staff']), false);

        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'Create'));
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
                'type' => 'student',
                'first_name' => 'Ana',
                'last_name' => 'Santos',
                'email' => 'ana.santos@example.com',
                'course' => 'BSN',
                'blood_type' => 'O+',
                'emergency_contact_person' => 'Maria Santos',
                'emergency_contact_number' => '+639171234567',
                'is_under_accessibility' => '1',
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
        $this->assertTrue($patient->is_under_accessibility);
    }

    public function test_clinic_user_can_create_staff_patient_record(): void
    {
        Department::create(['name' => 'Faculty']);

        $response = $this->actingAs($this->clinicUser())
            ->post(route('clinic.patients.store'), [
                'type' => 'staff',
                'first_name' => 'Ramon',
                'last_name' => 'Reyes',
                'email' => 'ramon.reyes@example.com',
                'course' => 'Must be cleared',
                'department' => 'Faculty',
                'position' => 'Instructor',
                'blood_type' => 'A+',
                'is_under_accessibility' => '1',
            ]);

        $patient = Patient::where('email', 'ramon.reyes@example.com')->firstOrFail();

        $response->assertRedirect(route('clinic.patients.show', ['patient' => $patient, 'tab' => 'staff']));
        $this->assertSame('staff', $patient->type);
        $this->assertNull($patient->course);
        $this->assertSame('Faculty', $patient->department);
        $this->assertSame('Instructor', $patient->position);
        $this->assertFalse($patient->is_under_accessibility);
    }

    public function test_student_patient_profile_displays_accessibility_status(): void
    {
        $patient = Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'type' => 'student',
            'is_under_accessibility' => true,
        ]);

        $this->actingAs($this->clinicUser())
            ->get(route('clinic.patients.show', $patient))
            ->assertOk()
            ->assertSee('Under Accessibility')
            ->assertSee('Yes');
    }

    public function test_recent_clinic_visits_display_both_teachers_without_email_status(): void
    {
        $patient = Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'type' => 'student',
        ]);

        ClinicConsultation::create([
            'patient_id' => $patient->id,
            'time_in' => now()->subMinutes(30),
            'time_out' => now(),
            'check_in_teacher' => 'Check-in Teacher',
            'check_in_teacher_email' => 'checkin.teacher@example.com',
            'current_teacher' => 'Checkout Teacher',
            'teacher_email' => 'checkout.teacher@example.com',
            'after_consultation' => 'resume',
            'email_status' => ClinicConsultation::EMAIL_STATUS_FAILED,
        ]);

        $this->actingAs($this->clinicUser())
            ->get(route('clinic.patients.show', $patient))
            ->assertOk()
            ->assertSeeText('Check-in Teacher')
            ->assertSeeText('Check-out Teacher')
            ->assertSeeText('Checkout Teacher')
            ->assertDontSeeText('Email Notification')
            ->assertDontSeeText('Failed');
    }

    public function test_staff_patient_department_must_exist(): void
    {
        $this->actingAs($this->clinicUser())
            ->from(route('clinic.patients.create', ['tab' => 'staff']))
            ->post(route('clinic.patients.store'), [
                'type' => 'staff',
                'first_name' => 'Invalid',
                'last_name' => 'Department',
                'department' => 'Not A Department',
            ])
            ->assertRedirect(route('clinic.patients.create', ['tab' => 'staff']))
            ->assertSessionHasErrors('department');
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
                'type' => 'student',
                'first_name' => 'New',
                'last_name' => 'Student',
                'email' => 'student@example.com',
                'course' => 'BSN',
            ])
            ->assertRedirect(route('clinic.patients.create', ['tab' => 'students']))
            ->assertSessionHasErrors('email');
    }
}
