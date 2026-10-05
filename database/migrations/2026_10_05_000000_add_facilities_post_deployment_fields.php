<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('floor', 50)->nullable();
        });

        DB::table('resources')->where('type', 'room')->select('id', 'location')->orderBy('id')
            ->chunk(100, function ($rooms) {
                foreach ($rooms as $room) {
                    $location = trim((string) $room->location);
                    if (preg_match('/^(Ground|\d+(?:st|nd|rd|th)) Floor$/i', $location)) {
                        DB::table('resources')->where('id', $room->id)->update(['floor' => $location]);
                    }
                }
            });

        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->string('payment_proof_path', 2048)->nullable();
        });

        Schema::table('resource_reservation_edits', function (Blueprint $table) {
            $table->boolean('email_available')->default(false);
            $table->timestamp('email_queued_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('resource_reservation_edits', fn (Blueprint $table) => $table->dropColumn(['email_available', 'email_queued_at']));
        Schema::table('resource_reservations', fn (Blueprint $table) => $table->dropColumn('payment_proof_path'));
        Schema::table('resources', fn (Blueprint $table) => $table->dropColumn('floor'));
    }
};
