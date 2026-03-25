<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DropClientAndConsultationTables extends Command
{
    protected $signature = 'db:drop-client-consultation-tables';

    protected $description = 'Drop clients and consultations tables (one-time use)';

    public function handle(): int
    {
        $tables = [
            'consultations',
            'clients',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
                $this->info("✅ Dropped table: {$table}");
            } else {
                $this->warn("⚠️ Table does not exist: {$table}");
            }
        }

        $this->info('🎯 Done.');

        return Command::SUCCESS;
    }
}
