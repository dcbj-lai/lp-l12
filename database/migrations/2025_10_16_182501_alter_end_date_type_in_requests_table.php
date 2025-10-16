<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('requests', function (Blueprint $table) {
        // Temporarily rename the old column
        $table->renameColumn('end_date_type', 'old_end_date_type');
    });

    Schema::table('requests', function (Blueprint $table) {
        // Add a new string column
        $table->string('end_date_type')->default('full');
    });

    // Copy data from old to new column
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
