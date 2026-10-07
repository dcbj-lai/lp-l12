<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->timestamp('finished_confirmed_at')->nullable();
            $table->foreignId('finished_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finished_confirmed_by');
            $table->dropColumn('finished_confirmed_at');
        });
    }
};
