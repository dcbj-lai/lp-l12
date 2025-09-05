<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        // Add CHECK constraint separately (Postgres style)
        DB::statement("ALTER TABLE requests ADD CONSTRAINT requests_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled'));");
    }

    public function down()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
        });

        DB::statement("ALTER TABLE requests DROP CONSTRAINT IF EXISTS requests_status_check;");
    }

};

