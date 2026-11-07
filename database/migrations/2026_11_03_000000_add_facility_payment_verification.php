<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        Role::findOrCreate('facility.payment-verifier', 'web');
        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->foreignId('payment_verifier_id')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('facility_payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations');
            $table->string('token_hash', 64)->unique();
            $table->string('recipient_hash', 64);
            $table->string('soa_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('facility_payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations');
            $table->foreignId('access_link_id')->constrained('facility_payment_links');
            $table->string('soa_path');
            $table->date('paid_on');
            $table->string('reference', 255);
            $table->json('files');
            $table->string('status')->default('pending')->index();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_payment_submissions');
        Schema::dropIfExists('facility_payment_links');
        Schema::table('resource_reservations', fn (Blueprint $table) => $table->dropConstrainedForeignId('payment_verifier_id'));
    }
};
