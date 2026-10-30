<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facility_reservation_access_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('recipient_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('facility_reservation_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations')->cascadeOnDelete();
            $table->foreignId('access_link_id')->constrained('facility_reservation_access_links');
            $table->string('status', 20); // review, cancelled, declined
            $table->text('reason')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_reservation_cancellations');
        Schema::dropIfExists('facility_reservation_access_links');
    }
};
