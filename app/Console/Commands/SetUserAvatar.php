<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SetUserAvatar extends Command
{
    protected $signature = 'user:set-avatar 
                            {email : User email} 
                            {url : Full S3 URL of avatar}';

    protected $description = 'Manually set user profile_photo_path from S3 URL';

    public function handle(): int
    {
        $email = $this->argument('email');
        $url = $this->argument('url');

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return self::FAILURE;
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error("Invalid URL provided.");
            return self::FAILURE;
        }

        // Extract path after bucket domain
        $parsed = parse_url($url);

        if (!isset($parsed['path'])) {
            $this->error("Unable to extract path from URL.");
            return self::FAILURE;
        }

        // Remove leading slash
        $path = ltrim($parsed['path'], '/');

        // Optional safety check
        if (!str_starts_with($path, 'avatars/')) {
            $this->warn("Path does not start with 'avatars/': {$path}");
        }

        // Update
        $user->update([
            'profile_photo_path' => $path,
        ]);

        $this->info("Avatar updated for {$email}");
        $this->line("Stored path: {$path}");

        return self::SUCCESS;
    }
}
