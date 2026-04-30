<?php

namespace App\Console\Commands;

use App\Models\ClinicConsultation;
use Illuminate\Console\Command;

class ClearClinicConsultations extends Command
{
    protected $signature = 'clinic:clear-consultations';

    protected $description = 'Clear all clinic consultations and display how many were cleared';

    public function handle(): int
    {
        $count = ClinicConsultation::count();

        if ($count === 0) {
            $this->info('No clinic consultations to clear.');
            return self::SUCCESS;
        }

        ClinicConsultation::query()->delete();

        $this->info("Cleared {$count} clinic consultation(s).");

        return self::SUCCESS;
    }
}