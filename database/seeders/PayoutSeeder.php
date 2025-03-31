<?php

namespace Database\Seeders;

use App\Models\Payout;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Payout::create([
            'control_number' => '2025-002',
            'pay_period_start' => '2025-03-21',
            'pay_period_end' => '2025-04-05',
            'payout_date' => '2025-04-10',
            'status' => 'pending',
            'total_amount' => 0,
            'cycle' => 1,
        ]);
    }
}
