<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ResourceRoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'facility.admin']);
        Role::firstOrCreate(['name' => 'facility.approver']);
        Role::firstOrCreate(['name' => 'facility.user']);
    }
}
