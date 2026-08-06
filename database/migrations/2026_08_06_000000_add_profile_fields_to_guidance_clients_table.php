<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_under_accessibility')->default(false)->after('section');
            $table->string('emergency_contact_person')->nullable()->after('is_under_accessibility');
            $table->string('emergency_contact_number', 50)->nullable()->after('emergency_contact_person');
            $table->string('blood_type', 10)->nullable()->after('emergency_contact_number');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'is_under_accessibility',
                'emergency_contact_person',
                'emergency_contact_number',
                'blood_type',
            ]);
        });
    }
};