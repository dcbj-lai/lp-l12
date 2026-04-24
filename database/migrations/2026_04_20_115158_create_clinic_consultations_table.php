<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_consultations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();

            $table->text('chief_complaint')->nullable();
            $table->string('case_classification')->nullable();

            // Vital Signs
            $table->string('blood_pressure')->nullable();
            $table->unsignedSmallInteger('pulse_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->unsignedSmallInteger('o2_saturation')->nullable();

            // Pain Rating Scale
            $table->unsignedTinyInteger('pain_rating')->nullable();

            $table->text('assessment')->nullable();
            $table->text('treatment')->nullable();

            // Dynamic rows
            $table->json('medicines')->nullable();
            $table->json('supplies')->nullable();

            $table->text('remarks')->nullable();

            // Photos upload
            $table->json('photo_attachments')->nullable();

            // student-only workflow fields
            $table->string('check_in_teacher')->nullable();
            $table->string('check_in_teacher_email')->nullable();
            $table->string('current_teacher')->nullable();
            $table->string('teacher_email')->nullable();

            $table->string('after_consultation')->nullable();
            $table->string('going_home_method')->nullable();
            $table->string('fetcher_name')->nullable();
            $table->string('self_approved_by')->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'time_in']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_consultations');
    }
};