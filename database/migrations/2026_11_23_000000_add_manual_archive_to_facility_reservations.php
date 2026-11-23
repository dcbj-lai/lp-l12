<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->timestamp('manually_archived_at')->nullable()->index();
            $table->foreignId('manually_archived_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manually_archived_by');
            $table->dropColumn('manually_archived_at');
        });
    }
};
