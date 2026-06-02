<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttachment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function registrantsCsv(Event $event): StreamedResponse
    {
        $registrations = $this->registrationsForReport($event);
        $customFieldLabels = $event->customFieldLabels();
        $fileName = $this->reportFileName($event, 'csv');

        $columns = array_merge([
            'Name',
            'Email',
            'Department',
            'Position',
            'Mobile',
            'Emergency Contact',
            'Emergency Relationship',
            'Emergency Phone',
            'Dietary Preference',
            'Allergies / Medical Notes',
            'Guests',
        ], array_values($customFieldLabels), [
            'Responded At',
        ]);

        return response()->stream(function () use ($registrations, $customFieldLabels, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($registrations as $registration) {
                $user = $registration->user;
                $row = [
                    $user?->preferred_name ?: $user?->name ?: '-',
                    $user?->email ?? '-',
                    $user?->department?->name ?? '-',
                    $user?->position ?? '-',
                    $user?->phone_mobile ?? '-',
                    $user?->emergency_contact_name ?? '-',
                    $user?->emergency_contact_relationship ?? '-',
                    $user?->emergency_contact_phone ?? '-',
                    $user?->dietary_preference ?? '-',
                    $user?->medical_notes ?? '-',
                    $registration->guest_count,
                ];

                foreach ($customFieldLabels as $index => $label) {
                    $row[] = $registration->customFieldAnswer((int) $index) ?: '-';
                }

                $row[] = optional($registration->responded_at)->format('Y-m-d H:i') ?? '-';

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function registrantsPdf(Event $event)
    {
        $registrations = $this->registrationsForReport($event);
        $customFieldLabels = $event->customFieldLabels();

        $pdf = Pdf::loadView('events.reports.registrants-pdf', [
            'event' => $event,
            'registrations' => $registrations,
            'customFieldLabels' => $customFieldLabels,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->reportFileName($event, 'pdf'));
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

    protected function registrationsForReport(Event $event)
    {
        return $event->registrations()
            ->with('user.department')
            ->where('status', 'attending')
            ->orderBy('responded_at')
            ->get();
    }

    protected function reportFileName(Event $event, string $extension): string
    {
        $slug = Str::slug($event->title) ?: 'event';

        return $slug . '_registrants_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
    }
}
