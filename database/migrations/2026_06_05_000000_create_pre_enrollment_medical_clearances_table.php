<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_enrollment_medical_clearances', function (Blueprint $table) {
            $table->id();
            $table->string('applicant_name');
            $table->string('email')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('intended_course')->nullable();
            $table->date('assessment_date');
            $table->string('clearance_status')->default('cleared');
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('issued_by_name');
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_enrollment_medical_clearances');
    }
};
