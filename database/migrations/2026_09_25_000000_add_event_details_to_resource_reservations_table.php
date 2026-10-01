<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            // Nullable so existing reservations remain valid. New requests will be
            // required to provide the appropriate fields by form/API validation.
            $table->unsignedInteger('number_of_pax')->nullable();
            $table->string('setup_arrangement')->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->text('consumables')->nullable();
            $table->string('floor_plan_path', 2048)->nullable();
            $table->string('gate_pass_path', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->dropColumn([
                'number_of_pax',
                'setup_arrangement',
                'contact_number',
                'consumables',
                'floor_plan_path',
                'gate_pass_path',
            ]);
        });
    }
};
