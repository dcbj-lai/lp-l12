<?php

namespace Database\Seeders;

use App\Models\Adjustment;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdjustmentSeederCycle2 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adjustments = [
            ['description' => 'Rice 1', 'amount' => 250, 'mode' => 'add'],
            ['description' => 'Rice 2', 'amount' => 250, 'mode' => 'add'],
            ['description' => 'Laundry', 'amount' => 300, 'mode' => 'add'],
            ['description' => 'Medicine', 'amount' => 800, 'mode' => 'add'],
            ['description' => 'Meal', 'amount' => 900, 'mode' => 'add'],
            ['description' => 'Other Allowances', 'amount' => 3000, 'mode' => 'add'],
        ];

        foreach ($adjustments as $adjustment) {
            Adjustment::create([
                'user_id' => 2,
                'cycle' => 2,
                'mode' => $adjustment['mode'],
                'description' => $adjustment['description'],
                'amount' => $adjustment['amount'],
                'effective_date' => '9999-12-31',
            ]);
        }
    }
}
