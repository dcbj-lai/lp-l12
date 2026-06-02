<?php

namespace Tests\Feature\Events;

use App\Livewire\Events\ManageEvents;
use App\Models\Event;
use App\Models\EventAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('events.manage', 'web');
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('events.manage');
    }

    public function test_admin_can_create_an_event(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('newEvent')
            ->set('title', 'Annual Team Building 2026')
            ->set('description', 'A day of fun.')
            ->set('location', 'Tagaytay')
            ->set('status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', [
            'title' => 'Annual Team Building 2026',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_name_four_custom_rsvp_fields_with_instructions(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('newEvent')
            ->set('title', 'Annual Team Building 2026')
            ->set('customFieldLabels.0', 'Dietary requirements')
            ->set('customFieldLabels.1', 'T-shirt size')
            ->set('customFieldLabels.2', 'Transport notes')
            ->set('customFieldLabels.3', 'Accessibility needs')
            ->set('customFieldInstructions.0', 'List any allergies or dietary restrictions.')
            ->set('customFieldInstructions.1', 'Enter your preferred shirt size.')
            ->set('customFieldInstructions.2', 'Add carpool, shuttle, or parking notes.')
            ->set('customFieldInstructions.3', 'Add access needs or seating requests.')
            ->call('save')
            ->assertHasNoErrors();

        $event = Event::where('title', 'Annual Team Building 2026')->firstOrFail();

        $this->assertSame([
            'Dietary requirements',
            'T-shirt size',
            'Transport notes',
            'Accessibility needs',
        ], $event->custom_field_labels);
        $this->assertSame([
            'List any allergies or dietary restrictions.',
            'Enter your preferred shirt size.',
            'Add carpool, shuttle, or parking notes.',
            'Add access needs or seating requests.',
        ], $event->custom_field_instructions);
    }

    public function test_title_is_required(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('newEvent')
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title' => 'required']);
    }

    public function test_admin_can_edit_an_event(): void
    {
        $event = Event::create(['title' => 'Old', 'status' => 'draft', 'created_by' => $this->admin->id]);

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('edit', $event->id)
            ->set('title', 'New Title')
            ->set('status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'New Title', 'status' => 'published']);
    }

    public function test_admin_can_delete_an_event(): void
    {
        $event = Event::create(['title' => 'Doomed', 'status' => 'draft']);

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('delete', $event->id);

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_instruction_file_is_uploaded_to_s3_and_recorded(): void
    {
        Storage::fake('private_s3');

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('newEvent')
            ->set('title', 'With Files')
            ->set('status', 'published')
            ->set('attachments', [UploadedFile::fake()->create('instructions.pdf', 120, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors();

        $event = Event::where('title', 'With Files')->firstOrFail();
        $attachment = EventAttachment::where('event_id', $event->id)->firstOrFail();

        $this->assertEquals('private_s3', $attachment->disk);
        $this->assertEquals('instructions.pdf', $attachment->original_name);
        Storage::disk('private_s3')->assertExists($attachment->file_path);
    }

    public function test_deleting_attachment_removes_file_and_record(): void
    {
        Storage::fake('private_s3');
        $event = Event::create(['title' => 'E', 'status' => 'published']);
        Storage::disk('private_s3')->put('events/1/file.pdf', 'data');
        $attachment = EventAttachment::create([
            'event_id' => $event->id,
            'file_path' => 'events/1/file.pdf',
            'original_name' => 'file.pdf',
            'disk' => 'private_s3',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('deleteAttachment', $attachment->id);

        $this->assertDatabaseMissing('event_attachments', ['id' => $attachment->id]);
        Storage::disk('private_s3')->assertMissing('events/1/file.pdf');
    }
}
