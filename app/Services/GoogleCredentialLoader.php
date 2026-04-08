<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class GoogleCredentialLoader
{
    public static function load(): string
    {
        $path = storage_path('app/google-calendar/service-account-credentials.json');

        if (file_exists($path)) {
            return $path;
        }

        $json = Storage::disk('secure_s3')->get('google/credentials.json');

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $json);

        return $path;
    }
}
