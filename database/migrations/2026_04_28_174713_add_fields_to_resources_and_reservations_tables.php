<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 🔹 Add internal control number to resources
        Schema::table('resources', function (Blueprint $table) {
            if (!Schema::hasColumn('resources', 'control_number')) {
                $table->string('control_number')
                    ->nullable()
                    ->unique()
                    ->after('name'); // safe, ignored in PG
            }
        });

        // 🔹 Add notes to reservations
        Schema::table('resource_reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('resource_reservations', 'notes')) {
                $table->text('notes')
                    ->nullable()
                    ->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            if (Schema::hasColumn('resources', 'control_number')) {
                $table->dropColumn('control_number');
            }
        });

        Schema::table('resource_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('resource_reservations', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
