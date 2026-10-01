<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->unsignedInteger('total_quantity')->default(1);
        });

        Schema::table('resource_reservation_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1);
        });

        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->uuid('recurrence_series_id')->nullable()->index();
            $table->string('billing_status', 20)->default('unbilled');
            $table->string('soa_path', 2048)->nullable();
            $table->date('soa_sent_at')->nullable();
            $table->date('payment_due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->softDeletes();
        });

        Schema::create('resource_reservation_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('resource_reservations')->cascadeOnDelete();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('before');
            $table->json('after');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_reservation_edits');

        Schema::table('resource_reservations', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['recurrence_series_id', 'billing_status', 'soa_path', 'soa_sent_at', 'payment_due_at', 'paid_at']);
        });

        Schema::table('resource_reservation_items', fn (Blueprint $table) => $table->dropColumn('quantity'));
        Schema::table('resources', fn (Blueprint $table) => $table->dropColumn('total_quantity'));
    }
};
