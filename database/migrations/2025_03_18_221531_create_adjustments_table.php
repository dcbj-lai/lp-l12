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
    Schema::create('adjustments', function (Blueprint $table) {
        $table->id();
        $table->enum('mode', ['add', 'subtract']);
        $table->string('description');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->integer('cycle');
        $table->decimal('amount', 15, 2);
        $table->date('effective_date')->default('9999-12-31'); // recurring by default
        $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null'); // Track who made the adjustment
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjustments');
    }
};
