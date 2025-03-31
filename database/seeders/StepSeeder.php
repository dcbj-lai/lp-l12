<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Step;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class StepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Generate data for the past 30 days
            for ($i = 0; $i < 3; $i++) {
                Step::create([
                    'user_id' => $user->id,
                    'steps' => rand(1000, 20000), // Random steps between 1k to 20k
                    'date' => Carbon::now()->subDays($i)->format('Y-m-d'),
                ]);
            }
        }

        $this->command->info('Steps seeded successfully!');
    }
}
