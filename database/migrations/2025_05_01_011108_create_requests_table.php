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
    Schema::create('requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
        $table->string('type'); // e.g., 'PTO', 'WFH', 'Offset'
        $table->text('reason');
        $table->date('start_date');
        $table->date('end_date');
        $table->enum('end_date_type', ['full', 'half'])->default('full');
        $table->decimal('number_of_days', 4, 2);
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->text('remarks')->nullable(); // Add this line
        $table->timestamps();
    });
    
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
