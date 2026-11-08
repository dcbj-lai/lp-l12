<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facility_payment_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('access_link_id')->nullable()->change();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('submission_note')->nullable();
            $table->date('previous_paid_on')->nullable();
            $table->json('previous_proof_ids')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('facility_payment_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn(['submission_note', 'previous_paid_on', 'previous_proof_ids']);
        });
        // Keep access_link_id nullable: retained manager submissions have no guest link.
    }
};
