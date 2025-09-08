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
    Schema::create('visitor_logs', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->string('mobile')->nullable();
        $table->string('otp', 6)->nullable();
        $table->string('full_name')->nullable();
        $table->string('address')->nullable();
        $table->string('purpose')->nullable();
        $table->string('status')->default('pending');
        $table->string('meetup_spot')->nullable();
        $table->foreignId('visited_user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('check_in_at')->nullable();
        $table->timestamp('check_out_at')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
