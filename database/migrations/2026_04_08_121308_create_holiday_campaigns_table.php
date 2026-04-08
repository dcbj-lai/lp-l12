<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('holiday_campaigns', function (Blueprint $table) {
            $table->id();
            $table->longText('html');
            $table->json('assets')->nullable();
            $table->string('subject')->nullable(); //
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_campaigns');
    }
};
