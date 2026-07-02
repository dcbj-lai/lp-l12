<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'legacy_roles')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('legacy_roles');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'legacy_roles')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('legacy_roles')->nullable();
            });
        }
    }
};
