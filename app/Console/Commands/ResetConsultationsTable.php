<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ResetConsultationsTable extends Command
{
    protected $signature = 'consultations:reset {--force : Run without confirmation}';
    protected $description = 'Drop consultations and clients tables, remove migration records, and delete migration files';

    public function handle(): int
    {
        $migrationNames = [
            '2026_02_24_160841_create_clients_table',
            '2026_02_24_170000_create_consultations_table',
            'bak.2026_02_24_133501_create_consultations_table',
            '2026_03_25_173600_alter_consultations_table_add_guidance_fields',
        ];

        $files = [
            database_path('migrations/2026_02_24_160841_create_clients_table.php'),
            database_path('migrations/2026_02_24_170000_create_consultations_table.php'),
            database_path('migrations/bak.2026_02_24_133501_create_consultations_table.php'),
            database_path('migrations/2026_03_25_173600_alter_consultations_table_add_guidance_fields.php'),
        ];

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                'This will DROP the consultations and clients tables, delete migration records, and remove the migration files. Continue?'
            );

            if (! $confirmed) {
                $this->info('Cancelled.');
                return self::SUCCESS;
            }
        }

        if (Schema::hasTable('consultations')) {
            Schema::drop('consultations');
            $this->info('Dropped table: consultations');
        } else {
            $this->warn('Table consultations does not exist.');
        }

        if (Schema::hasTable('clients')) {
            Schema::drop('clients');
            $this->info('Dropped table: clients');
        } else {
            $this->warn('Table clients does not exist.');
        }

        foreach ($migrationNames as $migrationName) {
            $deleted = DB::table('migrations')
                ->where('migration', $migrationName)
                ->delete();

            if ($deleted) {
                $this->info("Deleted migration record: {$migrationName}");
            } else {
                $this->warn("Migration record not found: {$migrationName}");
            }
        }

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $this->info("Deleted file: {$file}");
            } else {
                $this->warn("File not found: {$file}");
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}