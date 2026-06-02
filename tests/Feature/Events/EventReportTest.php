<?php

namespace Tests\Feature\Events;

use App\Models\Department;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('events.manage', 'web');

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('events.manage');

        $this->event = Event::create([
            'title' => 'Team Lunch',
            'status' => 'published',
            'custom_field_labels' => ['Dietary requirements', 'T-shirt size', ''],
        ]);
    }

    public function test_csv_report_includes_attending_signups_and_custom_answers(): void
    {
        $department = Department::create(['name' => 'Academics']);
        $attending = User::factory()->create([
            'name' => 'Jane Attendee',
            'email' => 'jane@example.com',
            'department_id' => $department->id,
            'position' => 'Teacher',
            'phone_mobile' => '09171234567',
            'emergency_contact_name' => 'Maria Santos',
            'emergency_contact_relationship' => 'Spouse',
            'emergency_contact_phone' => '+639171234567',
            'dietary_preference' => 'Vegetarian',
            'medical_notes' => 'Peanut allergy',
        ]);
        $declined = User::factory()->create(['name' => 'No Person']);

        EventRegistration::create([
            'event_id' => $this->event->id,
            'user_id' => $attending->id,
            'status' => 'attending',
            'guest_count' => 1,
            'custom_field_answers' => ['Vegetarian', 'Medium', ''],
            'responded_at' => now(),
        ]);
        EventRegistration::create([
            'event_id' => $this->event->id,
            'user_id' => $declined->id,
            'status' => 'not_attending',
            'responded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('events.registrants.csv', $this->event));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Jane Attendee', $csv);
        $this->assertStringContainsString('Emergency Contact', $csv);
        $this->assertStringContainsString('Maria Santos', $csv);
        $this->assertStringContainsString('Spouse', $csv);
        $this->assertStringContainsString('+639171234567', $csv);
        $this->assertStringContainsString('Peanut allergy', $csv);
        $this->assertStringContainsString('Dietary requirements', $csv);
        $this->assertStringContainsString('Vegetarian', $csv);
        $this->assertStringContainsString('Medium', $csv);
        $this->assertStringNotContainsString('No Person', $csv);
    }

    public function test_pdf_report_download_is_available_to_event_managers(): void
    {
        EventRegistration::create([
            'event_id' => $this->event->id,
            'user_id' => User::factory()->create()->id,
            'status' => 'attending',
            'responded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('events.registrants.pdf', $this->event));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_reports_are_forbidden_without_manage_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('events.registrants.csv', $this->event))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('events.registrants.pdf', $this->event))
            ->assertForbidden();
    }
}
