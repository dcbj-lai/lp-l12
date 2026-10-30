<?php

namespace App\Console\Commands;

use App\Models\FacilityBillingEmail;
use App\Models\FacilityPaymentProof;
use App\Models\FacilitySoaRevision;
use App\Models\ResourceReservation;
use App\Support\FacilityBillingStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivatizeFacilityBilling extends Command
{
    protected $signature = 'facilities:privatize-billing {--apply : Copy and verify files, then update references; never delete originals}';
    protected $description = 'Prepare a verified private copy of legacy facilities billing attachments';

    public function handle(): int
    {
        if ($this->option('apply') && app()->isProduction() && !app()->isDownForMaintenance()) {
            $this->error('Pause the application and queue workers before applying this migration.');
            return self::FAILURE;
        }
        if (FacilityBillingEmail::where('status', 'queued')->exists()) {
            $this->error('Drain or resolve queued billing emails before migrating their attachment references.');
            return self::FAILURE;
        }
        $paths = collect();
        $columns = ['soa_path', 'payment_proof_path'];
        ResourceReservation::withTrashed()->get($columns)->each(function ($row) use ($paths, $columns) {
            foreach ($columns as $column) if ($row->$column) $paths->push($row->$column);
        });
        $paths = $paths->merge(FacilityPaymentProof::pluck('path'))
            ->merge(FacilitySoaRevision::pluck('previous_path'))->merge(FacilitySoaRevision::pluck('replacement_path'))
            ->filter(fn ($path) => $path && !str_starts_with($path, 'facility-billing/'))->unique()->values();
        FacilityBillingEmail::each(function ($message) use ($paths) {
            $snapshot = $message->snapshot;
            $path = $snapshot['attachment'] ?? null;
            if ($path && !str_starts_with($path, 'facility-billing/')
                && ($snapshot['disk'] ?? null) === config('filesystems.facility_upload_disk')
                && FacilityBillingStorage::disk($path)->exists($path)) $paths->push($path);
        });
        $paths = $paths->unique()->values();
        $this->info($paths->count().' legacy attachment paths found.');
        if (!$this->option('apply')) {
            $this->info('Dry run: no files or database records changed.');
            return self::SUCCESS;
        }
        $diskName = FacilityBillingStorage::uploadDisk();
        $target = Storage::disk($diskName);
        $mapping = [];
        foreach ($paths as $old) {
            $source = FacilityBillingStorage::disk($old);
            if (!$source->exists($old)) {
                $this->error('An attachment is missing. No references updated. Restore missing files before continuing.');
                return self::FAILURE;
            }
            $bytes = $source->get($old);
            $new = 'facility-billing/legacy/'.Str::uuid().'.'.pathinfo($old, PATHINFO_EXTENSION);
            if (!is_string($bytes) || !$target->put($new, $bytes, ['visibility' => 'private'])
                || !hash_equals(hash('sha256', $bytes), hash('sha256', $target->get($new)))) {
                $this->error('Private copy verification failed. No references updated.');
                return self::FAILURE;
            }
            $mapping[$old] = ['path' => $new, 'sha256' => hash('sha256', $bytes)];
        }
        // Persist the cleanup inventory before updating any reference. Original files remain untouched.
        $manifest = 'facility-billing/migrations/'.Str::uuid().'.json';
        if (!$target->put($manifest, json_encode(['source_disk' => config('filesystems.facility_upload_disk'),
            'target_disk' => $diskName, 'mapping' => $mapping], JSON_THROW_ON_ERROR), ['visibility' => 'private'])) {
            $this->error('Could not save the migration inventory. No references updated.');
            return self::FAILURE;
        }
        DB::transaction(function () use ($mapping) {
            foreach ($mapping as $old => $copy) {
                foreach (['soa_path', 'payment_proof_path'] as $column) ResourceReservation::withTrashed()->where($column, $old)->update([$column => $copy['path']]);
                FacilityPaymentProof::where('path', $old)->update(['path' => $copy['path']]);
                foreach (['previous_path', 'replacement_path'] as $column) FacilitySoaRevision::where($column, $old)->update([$column => $copy['path']]);
            }
            FacilityBillingEmail::each(function ($message) use ($mapping) {
                $snapshot = $message->snapshot;
                if (isset($mapping[$snapshot['soa_path'] ?? ''])) {
                    $snapshot['soa_path'] = $mapping[$snapshot['soa_path']]['path'];
                }
                if (isset($mapping[$snapshot['attachment'] ?? ''])) {
                    $snapshot['attachment'] = $mapping[$snapshot['attachment']]['path'];
                    $snapshot['disk'] = FacilityBillingStorage::diskName($snapshot['attachment']);
                }
                $message->update(['snapshot' => $snapshot]);
            });
        });
        $this->info('Private references saved. Cleanup inventory: '.$manifest);
        $this->warn('Public originals still exist. Verify downloads, then remove only the inventoried originals through a separately approved cleanup.');
        return self::SUCCESS;
    }
}
