<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // User-confirmed PHP 755/day schedule. Preserve values already configured by an admin.
        $reference = ['weekday_ot' => 17949, 'sunday' => 18667, 'sunday_ot' => 24267,
            'special' => 4308, 'special_ot' => 24267, 'special_sunday' => 21539,
            'special_sunday_ot' => 28001, 'legal' => 14359, 'legal_ot' => 37335,
            'legal_sunday' => 37335, 'legal_sunday_ot' => 48535];
        foreach (['logistics', 'housekeeping'] as $staff) {
            foreach ($reference as $period => $amount) {
                DB::table('facility_charge_rates')->where('code', $staff.'_'.$period)->whereNull('amount_cents')
                    ->update(['amount_cents' => $amount, 'updated_at' => now()]);
            }
        }
        DB::table('facility_charge_rates')->where('code', 'security')->whereNull('amount_cents')
            ->update(['amount_cents' => 11594, 'updated_at' => now()]);
        DB::table('facility_charge_rates')->insertOrIgnore(['code' => 'security_night', 'amount_cents' => 12199,
            'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Pricing edits must survive rollback; do not erase admin configuration.
    }
};
