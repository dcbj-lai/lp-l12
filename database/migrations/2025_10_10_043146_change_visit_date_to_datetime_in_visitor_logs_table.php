<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dateTime('visit_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->date('visit_date')->change();
        });
    }
};

