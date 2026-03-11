<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearConsultations extends Command
{
    protected $signature = 'consultations:clear';
    protected $description = 'Delete all consultation records';

    public function handle(): int
    {
        DB::beginTransaction();

        try {
            $deleted = DB::table('consultations')->delete();

            DB::commit();

            $this->info("✅ Cleared {$deleted} consultation record(s).");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("❌ Failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}