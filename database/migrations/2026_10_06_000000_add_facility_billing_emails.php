<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->boolean('soa_email_pending')->default(false);
        });
        Schema::create('facility_billing_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 30);
            $table->string('status', 20)->default('queued');
            $table->json('snapshot');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_billing_emails');
        Schema::table('resource_reservations', fn (Blueprint $table) => $table->dropColumn('soa_email_pending'));
    }
};
