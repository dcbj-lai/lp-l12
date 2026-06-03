<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Always clear cache before seeding
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'leave.approve',
            'leave.delete',

            'reservation.approve',

            'users.edit',
            'users.authorize',
            'users.list',
            'users.compensation.manage',

            'leave-credits.view',
            'leave-credits.update',
            'leave-credits.assign',
            'leave-credits.initialize',

            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.manage',

            'requests.hr.view',
            'requests.hr.view.detail',
            'requests.hr.purge',

            // Events (PNC/HR manage; viewing is auth-only)
            'events.manage',

            // Access management
            'access.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Clear cache again after seeding
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
