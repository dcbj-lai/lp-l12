<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->string('recurrence_label')->nullable();
            $table->unsignedSmallInteger('recurrence_position')->nullable();
            $table->unsignedSmallInteger('recurrence_total')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->dropColumn(['recurrence_label', 'recurrence_position', 'recurrence_total']);
        });
    }
};
