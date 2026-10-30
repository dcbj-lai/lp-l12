<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facility_charge_rates', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->unsignedBigInteger('amount_cents')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        foreach (['security', 'logistics', 'housekeeping', 'plates', 'spoons', 'forks', 'coffee_set', 'cups'] as $code) {
            DB::table('facility_charge_rates')->insert(['code' => $code,
                'amount_cents' => in_array($code, ['logistics', 'housekeeping']) ? 20000 : null, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::create('facility_generated_soas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations')->cascadeOnDelete();
            $table->string('path')->unique();
            $table->json('snapshot');
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_generated_soas');
        Schema::dropIfExists('facility_charge_rates');
    }
};
