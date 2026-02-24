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

            // Student reference (this is the ONLY ID you need)
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

            // Time tracking
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();

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