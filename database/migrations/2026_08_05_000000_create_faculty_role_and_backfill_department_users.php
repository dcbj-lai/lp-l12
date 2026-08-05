<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $facultyRole = Role::findOrCreate('faculty', 'web');

        $facultyDepartmentId = DB::table('departments')
            ->whereRaw('LOWER(name) = ?', ['faculty'])
            ->value('id');

        if ($facultyDepartmentId !== null) {
            User::query()
                ->where('department_id', $facultyDepartmentId)
                ->chunkById(200, function ($users) use ($facultyRole) {
                    foreach ($users as $user) {
                        $user->assignRole($facultyRole);
                    }
                });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $facultyRole = Role::query()
            ->where('name', 'faculty')
            ->where('guard_name', 'web')
            ->first();

        if ($facultyRole) {
            $facultyRole->users()->detach();
            $facultyRole->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
