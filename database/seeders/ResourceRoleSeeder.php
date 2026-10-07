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
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'facilities.reservations.view-approved', 'guard_name' => 'web']);
        $pendingPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'facilities.reservations.view-pending', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'facility.viewer', 'guard_name' => 'web'])->syncPermissions([$permission, $pendingPermission]);
    }
}
