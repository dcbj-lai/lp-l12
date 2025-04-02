<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bible_verses', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('reference')->nullable(); // Added reference field
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bible_verses');
    }
};

