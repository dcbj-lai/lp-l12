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

            // Student reference
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

            // Time tracking
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();

            // Teacher + post-consultation outcome
            $table->string('current_teacher')->nullable();
            $table->string('after_consultation')->nullable();   // resume | go_home (or any string you decide)

            // Going home details
            $table->string('going_home_method')->nullable();    // fetcher | self
            $table->string('fetcher_name')->nullable();
            $table->string('self_approved_by')->nullable();

            // Consultation details
            $table->string('type_of_session')->nullable();
            $table->string('risk_assessment')->nullable();
            $table->text('issue_concern')->nullable();
            $table->text('intervention')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index(['client_id', 'time_in']);
            $table->index(['time_in']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};