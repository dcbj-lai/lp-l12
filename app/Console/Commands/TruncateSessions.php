<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateSessions extends Command
{
    protected $signature = 'sessions:truncate';

    protected $description = 'Truncate the sessions table';

    public function handle()
    {
        DB::table('sessions')->truncate();

        $this->info('Sessions table truncated.');

        return Command::SUCCESS;
    }
}
