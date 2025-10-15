<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\GoogleCalendar\Event;
use App\Helpers\GoogleCredentialHelper;

class TestGoogleCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google Calendar connection and list upcoming events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Checking Google Calendar credentials...');

        try {
            // Ensure credentials exist (auto-create if missing)
            $path = GoogleCredentialHelper::ensureCredentialsFile();
            config(['google-calendar.service_account_credentials_json' => $path]);

            $this->info('✅ Credentials OK: ' . $path);
        } catch (\Throwable $e) {
            $this->error('❌ Failed to prepare credentials: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('📅 Fetching upcoming events...');

        try {
            $events = Event::get();

            if ($events->isEmpty()) {
                $this->warn('No upcoming events found.');
            } else {
                foreach ($events as $event) {
                    $this->line("- {$event->name} ({$event->startDateTime} → {$event->endDateTime})");
                }
            }

            $this->info('✅ Google Calendar connection successful!');
        } catch (\Throwable $e) {
            $this->error('❌ Could not connect to Google Calendar: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
