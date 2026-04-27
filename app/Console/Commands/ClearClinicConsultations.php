<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearClinicConsultations extends Command
{
    protected $signature = 'clinic-consultations:clear';

    protected $description = 'Delete all clinic consultation records';

    public function handle(): int
    {
        DB::beginTransaction();

        try {
            $deleted = DB::table('clinic_consultations')->delete();

            DB::commit();

            $this->info("✅ Cleared {$deleted} clinic consultation record(s).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->error("❌ Failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}