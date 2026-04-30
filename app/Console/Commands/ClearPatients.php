<?php

namespace App\Console\Commands;

use App\Models\Patient;
use Illuminate\Console\Command;

class ClearPatients extends Command
{
    protected $signature = 'clinic:clear-patients';

    protected $description = 'Clear all patients and display how many were cleared';

    public function handle(): int
    {
        $count = Patient::count();

        if ($count === 0) {
            $this->info('No patients to clear.');
            return self::SUCCESS;
        }

        Patient::query()->delete();

        $this->info("Cleared {$count} patient(s).");

        return self::SUCCESS;
    }
}