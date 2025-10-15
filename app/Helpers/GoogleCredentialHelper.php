<?php

namespace App\Helpers;


class GoogleCredentialHelper
{
    public static function ensureCredentialsFile(): string
    {
        $path = storage_path('app/private/credentials.json');

        // If it already exists and non-empty, just return the path
        if (file_exists($path) && filesize($path) > 100) {
            return $path;
        }

        $base64 = env('GOOGLE_CALENDAR_CREDENTIALS_BASE64');

        if (!$base64) {
            throw new \Exception('Google credentials not configured.');
        }

        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            throw new \Exception('Invalid base64 credentials format.');
        }

        // Make sure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $decoded);

        return $path;
    }
}
