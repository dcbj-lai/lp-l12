<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;

class AmazonS3Service
{
    protected string $disk = 'private_s3';

    /**
     * Dynamically switch disk (e.g. to 's3' for public assets)
     */
    public function useDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Clean filename
     */
    public function sanitizeFilename(string $name): string
    {
        return preg_replace(
            '/[^A-Za-z0-9._-]/',
            '',
            str_replace(' ', '_', basename($name))
        );
    }

    /**
     * Generate timestamped unique filename
     */
    public function generateTimestampedName(string $originalName): string
    {
        $sanitized = $this->sanitizeFilename($originalName);

        $extension = pathinfo($sanitized, PATHINFO_EXTENSION);
        $nameOnly = pathinfo($sanitized, PATHINFO_FILENAME);

        $timestamp = now()->format('Ymd_His');

        return "{$nameOnly}_{$timestamp}.{$extension}";
    }

    /**
     * Upload file to selected disk
     */
    public function upload($file, string $folder): string
    {
        $finalName = $this->generateTimestampedName(
            $file->getClientOriginalName()
        );

        return $file->storeAs($folder, $finalName, $this->disk);
    }

    /**
     * Get full URL for a stored file
     */

    public function url(string $path): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        return $disk->url($path);
    }

    /**
     * Delete file if exists
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }
}
