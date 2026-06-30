<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $canonical = DB::table('users')
            ->where('employee_number', '2025112')
            ->orWhere(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%angela%tolentino%']);
            })
            ->orderByRaw("CASE WHEN employee_number = '2025112' THEN 0 ELSE 1 END")
            ->first();

        if (! $canonical) {
            return;
        }

        $duplicates = DB::table('users')
            ->where('id', '!=', $canonical->id)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['april tolentino'])
                    ->orWhere(function ($query) {
                        $query->whereRaw('LOWER(preferred_name) = ?', ['april'])
                            ->whereRaw('LOWER(name) LIKE ?', ['%tolentino%']);
                    });
            })
            ->where(function ($query) {
                $query->whereNull('employee_number')
                    ->orWhere('employee_number', '')
                    ->orWhere('employee_number', '-');
            })
            ->get();

        if ($duplicates->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($canonical, $duplicates) {
            foreach ($duplicates as $duplicate) {
                $this->mergeRequestCredit((int) $canonical->id, (int) $duplicate->id);
                $this->mergeReplenishmentItems($canonical, $duplicate);

                DB::table('requests')
                    ->where('user_id', $duplicate->id)
                    ->update(['user_id' => $canonical->id]);

                DB::table('users')
                    ->where('id', $duplicate->id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // One-way production data repair. Do not split merged leave history again.
    }

    private function mergeRequestCredit(int $canonicalUserId, int $duplicateUserId): void
    {
        $canonicalCredit = DB::table('request_credits')
            ->where('user_id', $canonicalUserId)
            ->orderByDesc('id')
            ->first();

        $duplicateCredits = DB::table('request_credits')
            ->where('user_id', $duplicateUserId)
            ->orderBy('id')
            ->get();

        foreach ($duplicateCredits as $duplicateCredit) {
            if (! $canonicalCredit) {
                DB::table('request_credits')
                    ->where('id', $duplicateCredit->id)
                    ->update([
                        'user_id' => $canonicalUserId,
                        'updated_at' => now(),
                    ]);

                $canonicalCredit = DB::table('request_credits')
                    ->where('id', $duplicateCredit->id)
                    ->first();

                continue;
            }

            DB::table('request_credits')
                ->where('id', $canonicalCredit->id)
                ->update([
                    'pto' => max((float) $canonicalCredit->pto, (float) $duplicateCredit->pto),
                    'wfh' => max((float) $canonicalCredit->wfh, (float) $duplicateCredit->wfh),
                    'approved_carry_over' => max(
                        (float) ($canonicalCredit->approved_carry_over ?? 0),
                        (float) ($duplicateCredit->approved_carry_over ?? 0)
                    ),
                    'updated_at' => now(),
                ]);

            DB::table('request_credits')
                ->where('id', $duplicateCredit->id)
                ->delete();

            $canonicalCredit = DB::table('request_credits')
                ->where('id', $canonicalCredit->id)
                ->first();
        }
    }

    private function mergeReplenishmentItems(object $canonical, object $duplicate): void
    {
        $duplicateItems = DB::table('leave_replenishment_run_items')
            ->where('user_id', $duplicate->id)
            ->orderBy('id')
            ->get();

        foreach ($duplicateItems as $duplicateItem) {
            $canonicalItem = DB::table('leave_replenishment_run_items')
                ->where('leave_replenishment_run_id', $duplicateItem->leave_replenishment_run_id)
                ->where('user_id', $canonical->id)
                ->orderByDesc('id')
                ->first();

            if (! $canonicalItem) {
                DB::table('leave_replenishment_run_items')
                    ->where('id', $duplicateItem->id)
                    ->update([
                        'user_id' => $canonical->id,
                        'employee_number' => $canonical->employee_number,
                        'employee_name' => $canonical->name,
                        'employee_email' => $canonical->email,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('leave_replenishment_run_items')
                ->where('id', $canonicalItem->id)
                ->update([
                    'previous_pto' => max((float) $canonicalItem->previous_pto, (float) $duplicateItem->previous_pto),
                    'previous_wfh' => max((float) $canonicalItem->previous_wfh, (float) $duplicateItem->previous_wfh),
                    'approved_carry_over_applied' => max(
                        (float) $canonicalItem->approved_carry_over_applied,
                        (float) $duplicateItem->approved_carry_over_applied
                    ),
                    'initialized_pto' => max((float) $canonicalItem->initialized_pto, (float) $duplicateItem->initialized_pto),
                    'initialized_wfh' => max((float) $canonicalItem->initialized_wfh, (float) $duplicateItem->initialized_wfh),
                    'updated_at' => now(),
                ]);

            DB::table('leave_replenishment_run_items')
                ->where('id', $duplicateItem->id)
                ->delete();
        }
    }
};
