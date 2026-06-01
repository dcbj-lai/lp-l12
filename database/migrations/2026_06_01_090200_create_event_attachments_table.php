<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Admin (PNC/HR) uploaded instruction files attached to an event.
     * Files are stored on S3 via App\Services\AmazonS3Service; only the
     * path/metadata is persisted here.
     */
    public function up(): void
    {
        Schema::create('event_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('file_path');               // S3 object key
            $table->string('original_name')->nullable();
            $table->string('disk')->default('private_s3');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attachments');
    }
};
