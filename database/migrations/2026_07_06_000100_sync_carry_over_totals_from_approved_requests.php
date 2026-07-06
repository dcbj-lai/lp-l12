<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $lastReplenishment = DB::table('leave_replenishment_runs')
            ->latest('created_at')
            ->value('created_at');

        $approvedCarryOvers = DB::table('requests')
            ->select('user_id', DB::raw('SUM(number_of_days) as approved_carry_over'))
            ->where('type', 'CREDIT_CARRY_OVER')
            ->where('status', 'approved')
            ->when($lastReplenishment, fn ($query) => $query->where('updated_at', '>=', $lastReplenishment))
            ->groupBy('user_id')
            ->get();

        foreach ($approvedCarryOvers as $carryOver) {
            $credit = DB::table('request_credits')
                ->where('user_id', $carryOver->user_id)
                ->first();

            $approvedCarryOver = min(
                (float) $carryOver->approved_carry_over,
                (float) ($credit?->pto ?? 0)
            );

            if ($credit) {
                DB::table('request_credits')
                    ->where('user_id', $carryOver->user_id)
                    ->update([
                        'approved_carry_over' => $approvedCarryOver,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('request_credits')->insert([
                'user_id' => $carryOver->user_id,
                'pto' => 0,
                'wfh' => 0,
                'approved_carry_over' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
