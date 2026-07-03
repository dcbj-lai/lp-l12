<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::where('name', 'pnc.super')
            ->where('guard_name', 'web')
            ->first();

        if ($role) {
            $role->users()->detach();
            $role->permissions()->detach();
            $role->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Intentionally no-op. pnc.super has been retired.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
