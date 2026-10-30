<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class FacilityBillingStorage
{
    public static function diskName(string $path): string
    {
        return str_starts_with($path, 'facility-billing/')
            ? config('filesystems.facility_billing_disk')
            : config('filesystems.facility_upload_disk');
    }

    public static function disk(string $path)
    {
        return Storage::disk(self::diskName($path));
    }

    public static function uploadDisk(): string
    {
        $disk = config('filesystems.facility_billing_disk');
        // Cloud instances cannot retain uploads on their ephemeral local filesystem.
        if (app()->isProduction() && ($disk === 'local' || config("filesystems.disks.$disk.visibility") !== 'private')) {
            throw new \RuntimeException('Private billing storage must be configured before uploading billing files.');
        }
        return $disk;
    }
}
