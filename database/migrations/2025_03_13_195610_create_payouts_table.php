<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('control_number')->unique();
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->date('payout_date');
            $table->dateTime('dispatched_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->enum('status', ['pending', 'dispatched', 'canceled'])->default('pending');
            $table->integer('cycle');
            $table->boolean('is_mailed')->default(false);
            $table->timestamps();

            $table->unique(['cycle', 'pay_period_start', 'pay_period_end', 'payout_date'], 'unique_cycle_period');
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
