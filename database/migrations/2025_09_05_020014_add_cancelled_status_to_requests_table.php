<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE requests DROP CONSTRAINT IF EXISTS requests_status_check');
            DB::statement("ALTER TABLE requests ADD CONSTRAINT requests_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled'))");

            return;
        }

        Schema::table('requests', function (Blueprint $table) {
            // Drop the old enum and recreate with the new values
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])
                  ->default('pending')
                  ->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE requests DROP CONSTRAINT IF EXISTS requests_status_check');
            DB::statement("ALTER TABLE requests ADD CONSTRAINT requests_status_check CHECK (status IN ('pending', 'approved', 'rejected'))");

            return;
        }

        Schema::table('requests', function (Blueprint $table) {
            // Rollback to original enum
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->change();
        });
    }
};
