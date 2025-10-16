<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Change enum to string so you can store flexible values
            $table->string('end_date_type')->default('full')->change();
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
