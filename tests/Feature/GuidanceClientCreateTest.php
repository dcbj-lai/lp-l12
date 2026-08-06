<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuidanceClientCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function guidanceUser(): User
    {
        Role::findOrCreate('guidance.admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('guidance.admin');

        return $user;
    }

    public function test_guidance_user_can_view_create_student_client_form(): void
    {
        $this->actingAs($this->guidanceUser())
            ->get(route('guidance.clients.create'))
            ->assertOk()
            ->assertSee('Create Student Client')
            ->assertSee('Course')
            ->assertSee('Section')
            ->assertSee('name="is_under_accessibility"', false)
            ->assertDontSee('Staff')
            ->assertDontSee('Select Department');
    }

    public function test_client_index_has_one_student_create_action_and_no_staff_tab(): void
    {
        $response = $this->actingAs($this->guidanceUser())
            ->get(route('guidance.clients.index'))
            ->assertOk()
            ->assertSee(route('guidance.clients.create'), false)
            ->assertSeeText('Student Clients')
            ->assertDontSeeText('Staff');

        $this->assertSame(1, substr_count($response->getContent(), route('guidance.clients.create')));
    }

    public function test_non_guidance_user_cannot_view_create_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('guidance.clients.create'))
            ->assertForbidden();
    }

    public function test_guidance_user_can_create_student_client(): void
    {
        $response = $this->actingAs($this->guidanceUser())
            ->post(route('guidance.clients.store'), [
                'first_name' => 'Ana',
                'last_name' => 'Santos',
                'email' => 'ana.guidance@example.com',
                'course' => 'BSN',
                'section' => 'A',
                'is_under_accessibility' => '1',
                'blood_type' => 'O+',
                'emergency_contact_person' => 'Maria Santos',
                'emergency_contact_number' => '+639171234567',
            ]);

        $client = Client::where('email', 'ana.guidance@example.com')->firstOrFail();
        $response->assertRedirect(route('guidance.clients.show', $client));
        $this->assertSame('BSN', $client->course);
        $this->assertSame('A', $client->section);
        $this->assertTrue($client->is_under_accessibility);
        $this->assertSame('O+', $client->blood_type);
        $this->assertSame('Maria Santos', $client->emergency_contact_person);
        $this->assertSame('+639171234567', $client->emergency_contact_number);
    }

    public function test_student_profile_displays_accessibility_status(): void
    {
        $client = Client::create([
            'first_name' => 'Accessible',
            'last_name' => 'Student',
            'email' => 'accessible.guidance@example.com',
            'is_under_accessibility' => true,
        ]);

        $this->actingAs($this->guidanceUser())
            ->get(route('guidance.clients.show', $client))
            ->assertOk()
            ->assertSeeText('Under Accessibility')
            ->assertSeeText('Yes')
            ->assertDontSeeText('Client Type')
            ->assertDontSeeText('Department')
            ->assertDontSeeText('Position');
    }

    public function test_recent_consultations_display_both_teachers_without_email_status(): void
    {
        $client = Client::create([
            'first_name' => 'Ana',
            'last_name' => 'Student',
            'email' => 'recent.guidance@example.com',
        ]);

        Consultation::create([
            'client_id' => $client->id,
            'time_in' => now()->subMinutes(30),
            'time_out' => now(),
            'check_in_teacher' => 'Check-in Teacher',
            'check_in_teacher_email' => 'checkin.guidance@example.com',
            'current_teacher' => 'Check-out Teacher',
            'teacher_email' => 'checkout.guidance@example.com',
            'after_consultation' => 'resume',
            'email_status' => Consultation::EMAIL_STATUS_FAILED,
        ]);

        $this->actingAs($this->guidanceUser())
            ->get(route('guidance.clients.show', $client))
            ->assertOk()
            ->assertSeeText('Check-in Teacher')
            ->assertSeeText('Check-out Teacher')
            ->assertDontSeeText('Email Notification')
            ->assertDontSeeText('Failed');
    }

    public function test_client_email_must_be_unique(): void
    {
        Client::create([
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'email' => 'duplicate.guidance@example.com',
        ]);

        $this->actingAs($this->guidanceUser())
            ->post(route('guidance.clients.store'), [
                'first_name' => 'New',
                'last_name' => 'Student',
                'email' => 'duplicate.guidance@example.com',
            ])
            ->assertSessionHasErrors('email');
    }
}