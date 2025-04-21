<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SuperUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'sanyalopez@gmail.com'],
            [
                'name' => 'Super User',
                'password' => Hash::make('1234567890'), // Default password
                'roles' => ['user','super.admin','sys.admin','pnc.admin','pnc.staff','finance.staff','finance.admin'],
                'google_id' => '000000000000000000001',
            ]
        );

        echo "✅ Super User seeded: {$user->email}\n";
    }
}
