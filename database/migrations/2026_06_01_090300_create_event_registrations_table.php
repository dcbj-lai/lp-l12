<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A staff member's RSVP to an event (the attend / not-attending toggle).
     * Profile-level data (name, contact, dietary, medical, emergency contact)
     * is NOT duplicated here — it is read from the user profile at view time.
     */
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('attending'); // attending | not_attending
            $table->unsignedSmallInteger('guest_count')->default(0);
            // shirt_size: parked here per "leftover -> event-specific"; drop if unused.
            $table->string('shirt_size')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
