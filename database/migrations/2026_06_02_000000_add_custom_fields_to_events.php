<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('custom_field_labels')->nullable()->after('status');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->json('custom_field_answers')->nullable()->after('guest_count');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('custom_field_answers');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('custom_field_labels');
        });
    }
};
