<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function codes(): array
    {
        $periods = ['weekday_ot', 'sunday', 'sunday_ot', 'special', 'special_ot', 'special_sunday',
            'special_sunday_ot', 'legal', 'legal_ot', 'legal_sunday', 'legal_sunday_ot'];
        $codes = [];
        foreach (['security', 'logistics', 'housekeeping'] as $staff) {
            foreach ($periods as $period) $codes[] = $staff.'_'.$period;
        }
        return $codes;
    }

    public function up(): void
    {
        foreach ($this->codes() as $code) {
            DB::table('facility_charge_rates')->insertOrIgnore(['code' => $code, 'amount_cents' => null,
                'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('facility_charge_rates')->whereIn('code', $this->codes())->delete();
    }
};
