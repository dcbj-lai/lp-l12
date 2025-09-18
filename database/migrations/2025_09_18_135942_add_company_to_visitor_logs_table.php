<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('visitor_logs', function (Blueprint $table) {
        $table->string('company')->nullable()->after('full_name');
    });
}

public function down()
{
    Schema::table('visitor_logs', function (Blueprint $table) {
        $table->dropColumn('company');
    });
}
};
