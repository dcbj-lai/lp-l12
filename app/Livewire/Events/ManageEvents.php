<?php

namespace App\Livewire\Events;

use App\Mail\EventInvitation;
use App\Models\Event;
use App\Models\EventAttachment;
use App\Models\User;
use App\Services\AmazonS3Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class ManageEvents extends Component
{
    use WithFileUploads;

    public bool $showForm = false;
    public ?int $editingId = null;

    // Invite-on-publish modal state
    public bool $showInviteModal = false;
    public ?int $inviteEventId = null;
    public array $selectedInvitees = [];

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:5000')]
    public string $description = '';

    #[Validate('nullable|string|max:255')]
    public string $location = '';

    #[Validate('nullable|date')]
    public $start_datetime = null;

    #[Validate('nullable|date|after_or_equal:start_datetime')]
    public $end_datetime = null;

    #[Validate('nullable|date')]
    public $rsvp_deadline = null;

    #[Validate('required|in:draft,published')]
    public string $status = 'draft';

    #[Validate(['attachments.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'])]
    public array $attachments = [];

    public function newEvent()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id)
    {
        $event = Event::findOrFail($id);
        $this->editingId = $event->id;
        $this->title = $event->title;
        $this->description = $event->description ?? '';
        $this->location = $event->location ?? '';
        $this->start_datetime = optional($event->start_datetime)->format('Y-m-d\TH:i');
        $this->end_datetime = optional($event->end_datetime)->format('Y-m-d\TH:i');
        $this->rsvp_deadline = optional($event->rsvp_deadline)->format('Y-m-d\TH:i');
        $this->status = $event->status;
        $this->attachments = [];
        $this->showForm = true;
    }

    public function save(AmazonS3Service $s3)
    {
        $this->validate();

        // Track whether this save transitions the event into "published"
        $previousStatus = $this->editingId ? optional(Event::find($this->editingId))->status : null;

        $event = Event::updateOrCreate(
            ['id' => $this->editingId],
            [
                'title' => $this->title,
                'description' => $this->description ?: null,
                'location' => $this->location ?: null,
                'start_datetime' => $this->start_datetime ?: null,
                'end_datetime' => $this->end_datetime ?: null,
                'rsvp_deadline' => $this->rsvp_deadline ?: null,
                'status' => $this->status,
                'created_by' => $this->editingId ? $event->created_by ?? Auth::id() : Auth::id(),
            ]
        );

        // Upload any instruction attachments to S3 (private by default)
        foreach ($this->attachments as $file) {
            if (!$file) {
                continue;
            }
            $path = $s3->upload($file, "events/{$event->id}");
            EventAttachment::create([
                'event_id' => $event->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'disk' => 'private_s3',
                'uploaded_by' => Auth::id(),
            ]);
        }

        $this->dispatch('flash', type: 'success', message: $this->editingId ? 'Event updated.' : 'Event created.');

        // If the event just became published, offer to invite users.
        $justPublished = $event->status === 'published' && $previousStatus !== 'published';

        $this->resetForm();
        $this->showForm = false;

        if ($justPublished) {
            $this->inviteEventId = $event->id;
            $this->selectedInvitees = [];
            $this->showInviteModal = true;
        }
    }

    /* ===================== Invite-on-publish ===================== */

    public function selectAllInvitees()
    {
        $this->selectedInvitees = User::orderBy('name')->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function clearInvitees()
    {
        $this->selectedInvitees = [];
    }

    public function sendInvites()
    {
        $event = Event::find($this->inviteEventId);

        if (!$event) {
            $this->closeInviteModal();
            return;
        }

        $users = User::whereIn('id', $this->selectedInvitees)
            ->whereNotNull('email')
            ->get();

        foreach ($users as $user) {
            Mail::to($user->email)->queue(new EventInvitation($event, $user->preferred_name ?? $user->name));
        }

        $this->dispatch('flash', type: 'success', message: $users->count() . ' invitation(s) sent.');
        $this->closeInviteModal();
    }

    public function closeInviteModal()
    {
        $this->showInviteModal = false;
        $this->inviteEventId = null;
        $this->selectedInvitees = [];
    }

    public function deleteAttachment(int $attachmentId, AmazonS3Service $s3)
    {
        $attachment = EventAttachment::find($attachmentId);
        if ($attachment) {
            $s3->useDisk($attachment->disk ?? 'private_s3')->delete($attachment->file_path);
            $attachment->delete();
            $this->dispatch('flash', type: 'success', message: 'Attachment removed.');
        }
    }

    public function delete(int $id, AmazonS3Service $s3)
    {
        $event = Event::with('attachments')->find($id);
        if (!$event) {
            return;
        }
        foreach ($event->attachments as $attachment) {
            $s3->useDisk($attachment->disk ?? 'private_s3')->delete($attachment->file_path);
        }
        $event->delete(); // cascades attachments + registrations
        $this->dispatch('flash', type: 'success', message: 'Event deleted.');
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'title', 'description', 'location',
            'start_datetime', 'end_datetime', 'rsvp_deadline', 'attachments',
        ]);
        $this->status = 'draft';
    }

    public function render()
    {
        $events = Event::with('attachments')
            ->withCount(['attendingRegistrations as attending_count'])
            ->latest()
            ->get();

        $invitees = $this->showInviteModal
            ? User::orderBy('name')->get(['id', 'name', 'preferred_name', 'email'])
            : collect();

        return view('livewire.events.manage-events', compact('events', 'invitees'));
    }
}
