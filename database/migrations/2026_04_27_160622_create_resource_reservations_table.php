<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resource_reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->nullable()->constrained('resources')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->string('google_event_id')->nullable();

            $table->timestamps();

            // 🔥 Important index for conflict checks
            $table->index(
                ['resource_id', 'start_datetime', 'end_datetime'],
                'res_res_time_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_reservations');
    }
};
