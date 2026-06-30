<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_replenishment_runs', function (Blueprint $table) {
            $table->id();
            $table->date('run_date');
            $table->decimal('pto_default', 5, 2);
            $table->decimal('wfh_default', 5, 2);
            $table->unsignedInteger('users_count')->default(0);
            $table->decimal('total_approved_carry_over', 8, 2)->default(0);
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_replenishment_runs');
    }
};
