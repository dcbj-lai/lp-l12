<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->boolean('payment_required')->default(true);
            $table->text('completion_notes')->nullable();
        });
        // Preserve the earlier explicit no-payment completion workflow, including deleted records.
        DB::table('resource_reservations')->whereNotNull('finished_confirmed_at')
            ->where('billing_status', 'unbilled')->whereNull('soa_path')->whereNull('paid_at')
            ->update(['payment_required' => false]);
    }

    public function down(): void
    {
        Schema::table('resource_reservations', fn (Blueprint $table) => $table->dropColumn(['payment_required', 'completion_notes']));
    }
};
