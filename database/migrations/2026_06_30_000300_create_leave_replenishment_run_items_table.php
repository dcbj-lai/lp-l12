<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_replenishment_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_replenishment_run_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number')->nullable();
            $table->string('employee_name');
            $table->string('employee_email')->nullable();
            $table->decimal('previous_pto', 5, 2)->default(0);
            $table->decimal('previous_wfh', 5, 2)->default(0);
            $table->decimal('pto_default', 5, 2)->default(0);
            $table->decimal('wfh_default', 5, 2)->default(0);
            $table->decimal('approved_carry_over_applied', 5, 2)->default(0);
            $table->decimal('initialized_pto', 5, 2)->default(0);
            $table->decimal('initialized_wfh', 5, 2)->default(0);
            $table->timestamps();

            $table->index(['leave_replenishment_run_id', 'employee_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_replenishment_run_items');
    }
};
