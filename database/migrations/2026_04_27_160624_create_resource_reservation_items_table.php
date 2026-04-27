<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resource_reservation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_id')
                ->constrained('resource_reservations')
                ->cascadeOnDelete();

            $table->foreignId('resource_id')
                ->constrained('resources')
                ->cascadeOnDelete();

            $table->timestamps();

            // prevent duplicate equipment in same reservation
            $table->unique(['reservation_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_reservation_items');
    }
};
