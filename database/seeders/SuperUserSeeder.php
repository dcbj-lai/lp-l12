<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['user', 'access.admin', 'super.admin', 'sys.admin', 'pnc.super', 'pnc.admin', 'pnc.staff', 'finance.staff', 'finance.admin'];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        $user = User::updateOrCreate(
            ['email' => 'laicportal000@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Makarios@nyn'), // Default password
                'google_id' => '000000000000000000001',
            ]
        );

        $user->syncRoles($roles);

        echo "✅ User seeded: {$user->email}\n";
    }
}
