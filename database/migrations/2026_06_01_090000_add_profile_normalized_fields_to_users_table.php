<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Person-level fields normalized onto the user profile so event
     * registration can auto-fill them instead of re-collecting per event.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_relationship');
            $table->string('dietary_preference')->nullable()->after('emergency_contact_phone');
            $table->text('medical_notes')->nullable()->after('dietary_preference');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_phone',
                'dietary_preference',
                'medical_notes',
            ]);
        });
    }
};
