<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = DB::table('org_settings')->first();
        $runDate = $settings?->last_leave_replenished_on ?: $now->toDateString();
        $ptoDefault = (float) ($settings?->pto_default ?? 0);
        $wfhDefault = (float) ($settings?->wfh_default ?? 0);

        $run = DB::table('leave_replenishment_runs')
            ->whereDate('run_date', $runDate)
            ->orderByDesc('id')
            ->first();

        $runId = $run?->id ?: DB::table('leave_replenishment_runs')->insertGetId([
            'run_date' => $runDate,
            'pto_default' => $ptoDefault,
            'wfh_default' => $wfhDefault,
            'users_count' => 0,
            'total_approved_carry_over' => 0,
            'run_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')
            ->leftJoin('request_credits', 'request_credits.user_id', '=', 'users.id')
            ->select([
                'users.id',
                'users.employee_number',
                'users.name',
                'users.email',
                'request_credits.pto',
                'request_credits.wfh',
                'request_credits.approved_carry_over',
            ])
            ->orderBy('users.id')
            ->chunkById(250, function ($users) use ($runId, $ptoDefault, $wfhDefault, $now) {
                foreach ($users as $user) {
                    $exists = DB::table('leave_replenishment_run_items')
                        ->where('leave_replenishment_run_id', $runId)
                        ->where('user_id', $user->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $currentPto = (float) ($user->pto ?? 0);
                    $currentWfh = (float) ($user->wfh ?? 0);

                    DB::table('leave_replenishment_run_items')->insert([
                        'leave_replenishment_run_id' => $runId,
                        'user_id' => $user->id,
                        'employee_number' => $user->employee_number,
                        'employee_name' => $user->name ?: ($user->email ?? 'Unknown employee'),
                        'employee_email' => $user->email,
                        'previous_pto' => $currentPto,
                        'previous_wfh' => $currentWfh,
                        'pto_default' => $ptoDefault,
                        'wfh_default' => $wfhDefault,
                        'approved_carry_over_applied' => (float) ($user->approved_carry_over ?? 0),
                        'initialized_pto' => $currentPto,
                        'initialized_wfh' => $currentWfh,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }, 'users.id', 'id');

        DB::table('leave_replenishment_runs')
            ->where('id', $runId)
            ->update([
                'users_count' => DB::table('leave_replenishment_run_items')
                    ->where('leave_replenishment_run_id', $runId)
                    ->count(),
                'total_approved_carry_over' => DB::table('leave_replenishment_run_items')
                    ->where('leave_replenishment_run_id', $runId)
                    ->sum('approved_carry_over_applied'),
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Data backfill only. Leave historical replenishment snapshots intact on rollback.
    }
};
