<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::findOrCreate('pnc.super', 'web');

        foreach ([
            'perly.gonzales@life.edu.ph',
            'don.balbieran@life.edu.ph',
        ] as $email) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->assignRole($role);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // One-way access grant. Remove manually if this operational role changes.
    }
};
