<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {

            if (!Schema::hasColumn('resource_reservations', 'requester_email')) {
                $table->string('requester_email')->nullable()->after('user_id');
            }

        });
    }

    public function down(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {

            if (Schema::hasColumn('resource_reservations', 'requester_email')) {
                $table->dropColumn('requester_email');
            }

        });
    }
};
