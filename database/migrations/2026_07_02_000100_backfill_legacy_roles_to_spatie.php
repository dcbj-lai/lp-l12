<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $knownRoles = [
            'user',
            'access.admin',
            'super.admin',
            'sys.admin',
            'pnc.staff',
            'pnc.super',
            'pnc.admin',
            'finance.staff',
            'finance.admin',
            'frontdesk.staff',
            'acad.admin',
            'guidance.admin',
            'guidance.staff',
            'comms.admin',
            'clinic.admin',
            'facility.admin',
            'facility.approver',
            'facility.user',
        ];

        foreach ($knownRoles as $role) {
            Role::findOrCreate($role, 'web');
        }

        User::query()
            ->select(['id', 'legacy_roles'])
            ->whereNotNull('legacy_roles')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $legacyRoles = $user->legacy_roles;

                    if (is_string($legacyRoles)) {
                        $decoded = json_decode($legacyRoles, true);
                        $legacyRoles = is_array($decoded) ? $decoded : [];
                    }

                    $roles = collect($legacyRoles ?? [])
                        ->filter(fn ($role) => is_string($role) && trim($role) !== '')
                        ->map(fn ($role) => trim($role))
                        ->unique()
                        ->values();

                    if ($roles->isEmpty()) {
                        continue;
                    }

                    if ($roles->contains('super.admin') && ! $roles->contains('access.admin')) {
                        $roles->push('access.admin');
                    }

                    foreach ($roles as $role) {
                        Role::findOrCreate($role, 'web');
                    }

                    $user->assignRole($roles->all());
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // One-way RBAC migration. Do not remove Spatie roles on rollback.
    }
};
