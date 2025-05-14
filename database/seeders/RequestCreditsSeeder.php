<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\RequestCredit;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RequestCreditsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ptoDefault = env('REQUESTS_PTO_DEFAULT', 0);
        $wfhDefault = env('REQUESTS_WFH_DEFAULT', 0);

        User::all()->each(function ($user) use ($ptoDefault, $wfhDefault) {
            RequestCredit::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'pto' => $ptoDefault,
                    'wfh' => $wfhDefault,
                    'offset' => 0,
                ]
            );
        });
    }
}
