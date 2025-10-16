<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 🧹 Drop the old enum constraint (PostgreSQL only)
        DB::statement('ALTER TABLE requests DROP CONSTRAINT IF EXISTS requests_end_date_type_check');

        // 🪄 Rename the old column to preserve data temporarily
        Schema::table('requests', function (Blueprint $table) {
            $table->renameColumn('end_date_type', 'old_end_date_type');
        });

        // 🆕 Add the new string column
        Schema::table('requests', function (Blueprint $table) {
            $table->string('end_date_type')->default('full');
        });

        // 🔁 Copy data from old column
        DB::statement('UPDATE requests SET end_date_type = old_end_date_type');

        // 🗑️ Drop the old column
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('old_end_date_type');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('end_date_type', ['full', 'half'])->default('full')->change();
        });
    }
};
