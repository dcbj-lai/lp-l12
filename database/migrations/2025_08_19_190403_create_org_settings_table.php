<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('pto_default', 5, 2)->default(0);
            $table->decimal('wfh_default', 5, 2)->default(0);
            $table->timestamps();
        });

        // seed with initial row
        \DB::table('org_settings')->insert([
            'pto_default' => 10,
            'wfh_default' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('org_settings');
    }
};
