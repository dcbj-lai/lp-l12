<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    // Drop the old check constraint if it exists
    DB::statement('ALTER TABLE requests DROP CONSTRAINT IF EXISTS requests_end_date_type_check');

    // Rename old column
    Schema::table('requests', function (Blueprint $table) {
        $table->renameColumn('end_date_type', 'old_end_date_type');
    });

    // Add new string column
    Schema::table('requests', function (Blueprint $table) {
        $table->string('end_date_type')->default('full');
    });

    // Copy over existing data
    DB::statement('UPDATE requests SET end_date_type = old_end_date_type');

    // Drop the old enum column
    Schema::table('requests', function (Blueprint $table) {
        $table->dropColumn('old_end_date_type');
    });
}



    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Revert back to enum if you ever roll back
            $table->enum('end_date_type', ['full', 'half'])->default('full')->change();
        });
    }
};
