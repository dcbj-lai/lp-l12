<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttachment;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Shared, read-only event detail view — available to ANY authenticated
     * user regardless of role. Staff RSVP via the embedded Livewire toggle.
     */
    public function show(Event $event)
    {
        $event->load(['attachments', 'creator']);
        $attendingCount = $event->attendingRegistrations()->count();

        return view('events.show', compact('event', 'attendingCount'));
    }

    /**
     * Full "see more" list of everyone registered for an event.
     * Public to all authenticated users.
     */
    public function registrants(Event $event)
    {
        $registrations = $event->registrations()
            ->with('user')
            ->orderBy('status') // 'attending' sorts before 'not_attending'
            ->orderBy('responded_at')
            ->get();

        return view('events.registrants', compact('event', 'registrations'));
    }

    /**
     * Stream a private-S3 instruction attachment to authenticated users.
     */
    public function attachment(EventAttachment $attachment)
    {
        $disk = Storage::disk($attachment->disk ?? 'private_s3');

        if (!$disk->exists($attachment->file_path)) {
            abort(404);
        }

        $stream = $disk->readStream($attachment->file_path);
        $mime = $disk->mimeType($attachment->file_path) ?? 'application/octet-stream';

        return response()->stream(
            fn() => fpassthru($stream),
            200,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . ($attachment->original_name ?? basename($attachment->file_path)) . '"',
            ]
        );
    }
}
