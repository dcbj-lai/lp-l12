<?php

namespace App\Services;

use App\Models\{ResourceReservation, User};
use App\Support\FacilityBillingStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacilityReservationArchiveService
{
    // Children must be removed before the access links they reference.
    private const TABLES = [
        'facility_payment_overrides', 'facility_payment_proofs', 'facility_soa_revisions',
        'facility_billing_emails', 'facility_generated_soas', 'facility_payment_submissions',
        'facility_payment_links', 'facility_reservation_cancellations', 'facility_reservation_access_links',
        'resource_reservation_edits', 'resource_reservation_items', 'resource_reservation_rooms',
    ];

    public function archive(int $id, User $actor, bool $restore = false): void
    {
        $this->authorize($actor);
        DB::transaction(function () use ($id, $actor, $restore) {
            $booking = ResourceReservation::withTrashed()->lockForUpdate()->findOrFail($id);
            if (!$restore && !$booking->trashed() && $booking->status === 'approved' && !$booking->is_archived) {
                throw ValidationException::withMessages(['archiveAction' => 'Approved reservations cannot be archived.']);
            }
            $booking->manually_archived_at = $restore ? null : now();
            $booking->manually_archived_by = $restore ? null : $actor->id;
            $booking->save();
        });
    }

    public function permanentlyDelete(int $id, User $actor): int
    {
        $this->authorize($actor);
        $unusedFiles = DB::transaction(function () use ($id) {
            $booking = ResourceReservation::withTrashed()->lockForUpdate()->findOrFail($id);
            if (!$booking->manually_archived_at) {
                throw ValidationException::withMessages(['archiveAction' => 'Archive the reservation before permanently deleting it.']);
            }
            if (!$booking->trashed() && $booking->status === 'approved' && !$booking->is_archived) {
                throw ValidationException::withMessages(['archiveAction' => 'Approved reservations cannot be permanently deleted.']);
            }
            if ($booking->billingEmails()->where('status', 'queued')->exists()) {
                throw ValidationException::withMessages(['archiveAction' => 'Wait for queued billing emails to finish before permanently deleting.']);
            }
            $files = $this->files($id);
            $shared = $this->files($id, true);
            if ($booking->google_event_id && !app(ResourceReservationService::class)->deleteGoogleCalendarEvent($booking)) {
                throw ValidationException::withMessages(['archiveAction' => 'Calendar removal failed. The reservation was not deleted.']);
            }
            foreach (self::TABLES as $table) DB::table($table)->where('reservation_id', $id)->delete();
            $booking->forceDelete();
            return array_values(array_diff($files, $shared));
        });
        $failed = 0;
        foreach ($unusedFiles as $path) {
            try {
                if (!FacilityBillingStorage::disk($path)->delete($path)) $failed++;
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }
        return $failed;
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->is_active && $actor->hasRole('facility.admin') && !$actor->hasRole('facility.viewer'), 403);
    }

    private function files(int $id, bool $others = false): array
    {
        $paths = [];
        $collect = function ($path) use (&$paths) {
            if (is_string($path) && preg_match('~^(reservations/|facility-billing/)[^\x00]+$~', $path) && !str_contains($path, '..')) $paths[] = $path;
        };
        $columns = [
            'resource_reservations' => ['floor_plan_path', 'gate_pass_path', 'attachment_path', 'soa_path', 'payment_proof_path'],
            'facility_payment_proofs' => ['path'], 'facility_soa_revisions' => ['previous_path', 'replacement_path'],
            'facility_generated_soas' => ['path'], 'facility_payment_submissions' => ['soa_path'],
            'facility_billing_emails' => [], 'resource_reservation_edits' => [],
        ];
        foreach ($columns as $table => $fields) {
            $column = $table === 'resource_reservations' ? 'id' : 'reservation_id';
            foreach (DB::table($table)->where($column, $others ? '!=' : '=', $id)->cursor() as $row) {
                foreach ($fields as $field) $collect($row->{$field});
                if ($table === 'facility_payment_submissions') {
                    foreach (json_decode($row->files, true) ?? [] as $file) $collect($file['path'] ?? null);
                }
                if ($table === 'facility_billing_emails') {
                    $snapshot = json_decode($row->snapshot, true) ?? [];
                    foreach (['soa_path', 'attachment'] as $field) $collect($snapshot[$field] ?? null);
                }
                if ($table === 'resource_reservation_edits') {
                    foreach (['before', 'after'] as $version) {
                        $snapshot = json_decode($row->{$version}, true) ?? [];
                        foreach ($columns['resource_reservations'] as $field) $collect($snapshot[$field] ?? null);
                    }
                }
            }
        }
        return array_values(array_unique($paths));
    }
}

