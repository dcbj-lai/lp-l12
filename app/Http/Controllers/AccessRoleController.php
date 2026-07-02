<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessRoleController extends Controller
{
    public function roles()
    {
        return response()->json([
            'data' => Role::with('permissions:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => $this->rolePayload($role))
                ->values(),
        ]);
    }

    public function userRoles(User $user)
    {
        return response()->json([
            'user' => $this->userRolePayload($user),
        ]);
    }

    public function assign(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required_without:roles', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'roles' => ['required_without:role', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $roles = $this->roleNames($validated);
        $user->assignRole($roles);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'updated' => true,
            'user' => $this->userRolePayload($user->fresh(['roles.permissions'])),
        ]);
    }

    public function sync(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $user->syncRoles(array_values(array_unique($validated['roles'])));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'updated' => true,
            'user' => $this->userRolePayload($user->fresh(['roles.permissions'])),
        ]);
    }

    public function revoke(User $user, string $role)
    {
        $roleModel = Role::where('guard_name', 'web')
            ->where('name', $role)
            ->firstOrFail();

        $user->removeRole($roleModel);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'updated' => true,
            'user' => $this->userRolePayload($user->fresh(['roles.permissions'])),
        ]);
    }

    protected function roleNames(array $validated): array
    {
        $roles = $validated['roles'] ?? [$validated['role']];

        return array_values(array_unique($roles));
    }

    protected function userRolePayload(User $user): array
    {
        $user->loadMissing('roles.permissions');

        return [
            'id' => $user->id,
            'employee_number' => $user->employee_number,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles
                ->sortBy('name')
                ->map(fn (Role $role) => $this->rolePayload($role))
                ->values(),
            'permissions' => $user->getPermissionsViaRoles()
                ->pluck('name')
                ->unique()
                ->sort()
                ->values(),
        ];
    }

    protected function rolePayload(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions
                ->pluck('name')
                ->sort()
                ->values(),
        ];
    }
}
