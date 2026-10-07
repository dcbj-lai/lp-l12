<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_reservation_rooms', function (Blueprint $table) {
            $table->foreignId('reservation_id')->constrained('resource_reservations')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->restrictOnDelete();
            $table->primary(['reservation_id', 'resource_id']);
            $table->index('resource_id');
        });
        DB::table('resource_reservations')->whereNotNull('resource_id')->orderBy('id')
            ->chunkById(500, function ($reservations) {
                DB::table('resource_reservation_rooms')->insert($reservations->map(fn ($reservation) => [
                    'reservation_id' => $reservation->id, 'resource_id' => $reservation->resource_id,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_reservation_rooms');
    }
};
