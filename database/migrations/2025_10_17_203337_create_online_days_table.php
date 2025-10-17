<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('declared_by')->nullable(); // admin name or user_id
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_days');
    }
};
