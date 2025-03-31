<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ripples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->string('file_path')->nullable();
            $table->boolean('pinned')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('ripples')->onDelete('cascade'); // Self-referencing for replies
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ripples');
    }
};
