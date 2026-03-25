<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteSpecificMigrations extends Command
{
    protected $signature = 'migrations:delete-once';

    protected $description = 'Delete specific migration records (no confirmation)';

    public function handle(): int
    {
        $migrations = [
            '2026_02_24_160841_create_clients_table',
            '2026_02_24_170000_create_consultations_table',
            'bak.2026_02_24_133501_create_consultations_table',
            '2026_03_25_173600_alter_consultations_table_add_guidance_fields',
        ];

        $deletedCount = DB::table('migrations')
            ->whereIn('migration', $migrations)
            ->delete();

        $this->info("✅ Deleted {$deletedCount} migration record(s).");

        return Command::SUCCESS;
    }
}
