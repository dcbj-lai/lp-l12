<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
//     public function up(): void
// {
//     // Drop the old check constraint if it exists
//     DB::statement('ALTER TABLE requests DROP CONSTRAINT IF EXISTS requests_end_date_type_check');

//     // Rename old column
//     Schema::table('requests', function (Blueprint $table) {
//         $table->renameColumn('end_date_type', 'old_end_date_type');
//     });

//     // Add new string column
//     Schema::table('requests', function (Blueprint $table) {
//         $table->string('end_date_type')->default('full');
//     });

//     // Copy over existing data
//     DB::statement('UPDATE requests SET end_date_type = old_end_date_type');

//     // Drop the old enum column
//     Schema::table('requests', function (Blueprint $table) {
//         $table->dropColumn('old_end_date_type');
//     });
// }
public function up(): void
{
    // 1. Add new string column
    Schema::table('requests', function (Blueprint $table) {
        $table->string('end_date_type_new')->default('full');
    });

    // 2. Copy data from enum to string
    DB::statement('UPDATE requests SET end_date_type_new = end_date_type');

    // 3. Drop old enum column
    Schema::table('requests', function (Blueprint $table) {
        $table->dropColumn('end_date_type');
    });

    // 4. Rename new column to original name
    Schema::table('requests', function (Blueprint $table) {
        $table->renameColumn('end_date_type_new', 'end_date_type');
    });
}


    // public function down(): void
    // {
    //     Schema::table('requests', function (Blueprint $table) {
    //         // Revert back to enum if you ever roll back
    //         $table->enum('end_date_type', ['full', 'half'])->default('full')->change();
    //     });
    // }
    public function down(): void
{
    // Recreate enum column
    Schema::table('requests', function (Blueprint $table) {
        $table->enum('end_date_type_old', ['full', 'half'])->default('full');
    });

    // Copy data back
    DB::statement('UPDATE requests SET end_date_type_old = end_date_type');

    // Drop string column
    Schema::table('requests', function (Blueprint $table) {
        $table->dropColumn('end_date_type');
    });

    // Rename enum back
    Schema::table('requests', function (Blueprint $table) {
        $table->renameColumn('end_date_type_old', 'end_date_type');
    });
}

};
