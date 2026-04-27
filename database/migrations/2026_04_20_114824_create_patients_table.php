<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('patients', function (Blueprint $table) {
        $table->id();

        $table->string('first_name');
        $table->string('last_name');
        $table->string('email')->nullable()->unique();

        $table->string('type')->default('student');

        // student fields
        $table->string('course')->nullable();

        // staff fields
        $table->string('department')->nullable();
        $table->string('position')->nullable();

        // NEW: emergency + blood type
        $table->string('emergency_contact_person')->nullable();
        $table->string('emergency_contact_number')->nullable();
        $table->string('blood_type')->nullable();

        $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
