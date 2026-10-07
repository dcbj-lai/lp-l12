<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facility_payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations');
            $table->string('path');
            $table->string('original_name');
            $table->date('paid_on')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('facility_soa_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations');
            $table->string('previous_path');
            $table->string('replacement_path');
            $table->text('reason');
            $table->foreignId('replaced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        DB::table('resource_reservations')->whereNotNull('payment_proof_path')->orderBy('id')->chunkById(100, function ($reservations) {
            foreach ($reservations as $reservation) {
                DB::table('facility_payment_proofs')->insert([
                    'reservation_id' => $reservation->id,
                    'path' => $reservation->payment_proof_path,
                    'original_name' => basename($reservation->payment_proof_path),
                    'paid_on' => $reservation->paid_at ? substr($reservation->paid_at, 0, 10) : null,
                    'created_at' => $reservation->updated_at,
                    'updated_at' => $reservation->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_soa_revisions');
        Schema::dropIfExists('facility_payment_proofs');
    }
};
