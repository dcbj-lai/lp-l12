<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['room', 'equipment']);
            $table->text('description')->nullable();
            $table->string('location')->nullable(); // for rooms
            $table->integer('capacity')->nullable(); // for rooms
            $table->string('image_path')->nullable(); // optional image for resource
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
