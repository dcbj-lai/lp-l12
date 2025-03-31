<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('payslips', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('payout_id')->constrained()->onDelete('cascade'); // Links to the Payout (Cycle)
        $table->decimal('basic_pay', 15, 2)->default(0.00);
        $table->decimal('total_additions', 15, 2)->default(0.00);
        $table->decimal('total_deductions', 15, 2)->default(0.00);
        $table->decimal('tax_withheld', 15, 2)->default(0.00);
        $table->decimal('net_pay', 15, 2);
        $table->json('adjustments')->nullable(); // Stores the breakdown of adjustments
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('payslips');
}

};
