<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->text('approval_note')
                ->nullable()
                ->after('google_event_id'); // ✅ correct placement
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->dropColumn('approval_note'); // ✅ proper rollback
        });
    }
};
