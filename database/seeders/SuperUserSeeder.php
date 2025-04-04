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
            ['email' => 'laicportal000@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('1234567890'), // Default password
                'roles' => ['user', 'super.admin'],
                'google_id' => '000000000000000000002',
            ]
        );

        echo "✅ Super User seeded: {$user->email}\n";
    }
}
