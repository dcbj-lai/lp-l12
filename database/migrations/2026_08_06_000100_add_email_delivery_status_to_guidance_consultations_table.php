<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->string('email_status', 20)->nullable()->after('self_approved_by')->index();
            $table->timestamp('email_sent_at')->nullable()->after('email_status');
            $table->timestamp('email_failed_at')->nullable()->after('email_sent_at');
            $table->text('email_failure_message')->nullable()->after('email_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndex(['email_status']);
            $table->dropColumn([
                'email_status',
                'email_sent_at',
                'email_failed_at',
                'email_failure_message',
            ]);
        });
    }
};
