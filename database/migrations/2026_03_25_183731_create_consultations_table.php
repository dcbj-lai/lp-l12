<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();

            $table->string('check_in_teacher')->nullable();
            $table->string('check_in_teacher_email')->nullable();
            $table->string('current_teacher')->nullable();
            $table->string('teacher_email')->nullable();

            $table->string('after_consultation')->nullable();
            $table->string('going_home_method')->nullable();
            $table->string('fetcher_name')->nullable();
            $table->string('self_approved_by')->nullable();

            $table->text('type_of_session')->nullable();
            $table->text('risk_assessment')->nullable();
            $table->text('issue_concern')->nullable();
            $table->text('intervention')->nullable();
            $table->text('remarks')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};