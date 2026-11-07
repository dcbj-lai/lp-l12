<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facility_payment_proofs', function (Blueprint $table) {
            $table->timestamp('superseded_at')->nullable();
            $table->foreignId('superseded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('superseded_reason')->nullable();
        });
        Schema::create('facility_payment_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations');
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->date('previous_paid_on')->nullable();
            $table->date('paid_on');
            $table->json('previous_proof_ids');
            $table->json('replacement_proof_ids');
            $table->json('submission_ids');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_payment_overrides');
        Schema::table('facility_payment_proofs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('superseded_by');
            $table->dropColumn(['superseded_at', 'superseded_reason']);
        });
    }
};
