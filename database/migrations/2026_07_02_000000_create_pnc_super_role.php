<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Role::findOrCreate('pnc.super', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::where('name', 'pnc.super')
            ->where('guard_name', 'web')
            ->first();

        if ($role && $role->users()->count() === 0) {
            $role->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
