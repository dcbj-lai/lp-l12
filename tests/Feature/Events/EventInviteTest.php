<?php

namespace Tests\Feature\Events;

use App\Livewire\Events\ManageEvents;
use App\Mail\EventInvitation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventInviteTest extends TestCase
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

    public function test_publishing_a_new_event_opens_the_invite_modal(): void
    {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('newEvent')
            ->set('title', 'TB')
            ->set('status', 'published')
            ->call('save')
            ->assertSet('showInviteModal', true);
    }

    public function test_saving_a_draft_does_not_open_the_invite_modal(): void
    {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('newEvent')
            ->set('title', 'Draft TB')
            ->set('status', 'draft')
            ->call('save')
            ->assertSet('showInviteModal', false);

        Mail::assertNothingQueued();
    }

    public function test_editing_an_already_published_event_does_not_reopen_modal(): void
    {
        $event = Event::create(['title' => 'Live', 'status' => 'published', 'created_by' => $this->admin->id]);

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('edit', $event->id)
            ->set('title', 'Live Edited')
            ->call('save')
            ->assertSet('showInviteModal', false);
    }

    public function test_select_all_picks_every_user(): void
    {
        User::factory()->count(3)->create();
        $total = User::count(); // includes admin

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('newEvent')
            ->set('title', 'TB')
            ->set('status', 'published')
            ->call('save')
            ->call('selectAllInvitees')
            ->assertCount('selectedInvitees', $total);
    }

    public function test_send_invites_queues_email_to_each_selected_user(): void
    {
        Mail::fake();
        $a = User::factory()->create();
        $b = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('newEvent')
            ->set('title', 'TB')
            ->set('status', 'published')
            ->call('save')
            ->set('selectedInvitees', [$a->id, $b->id])
            ->call('sendInvites')
            ->assertSet('showInviteModal', false);

        Mail::assertQueued(EventInvitation::class, fn ($mail) => $mail->hasTo($a->email));
        Mail::assertQueued(EventInvitation::class, fn ($mail) => $mail->hasTo($b->email));
        Mail::assertQueued(EventInvitation::class, 2);
    }

    public function test_skipping_invites_sends_nothing_but_event_stays_published(): void
    {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(ManageEvents::class)
            ->call('newEvent')
            ->set('title', 'TB Skip')
            ->set('status', 'published')
            ->call('save')
            ->call('closeInviteModal')
            ->assertSet('showInviteModal', false);

        Mail::assertNothingQueued();
        $this->assertDatabaseHas('events', ['title' => 'TB Skip', 'status' => 'published']);
    }
}
