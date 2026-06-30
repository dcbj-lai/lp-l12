<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_credits', function (Blueprint $table) {
            $table->decimal('approved_carry_over', 5, 2)->default(0)->after('wfh');
        });

        Schema::table('org_settings', function (Blueprint $table) {
            $table->date('last_leave_replenished_on')->nullable()->after('wfh_default');
        });
    }

    public function down(): void
    {
        Schema::table('org_settings', function (Blueprint $table) {
            $table->dropColumn('last_leave_replenished_on');
        });

        Schema::table('request_credits', function (Blueprint $table) {
            $table->dropColumn('approved_carry_over');
        });
    }
};
