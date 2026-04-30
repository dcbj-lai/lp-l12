<?php

namespace App\Livewire\Access;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesIndex extends Component
{
    public $roles = [];
    public $permissions = [];
    public $groupedPermissions = [];

    public $name = '';
    public $selectedPermissions = [];

    public function mount()
    {
        $this->loadRoles();
        $this->loadPermissions();
    }

    public function loadRoles()
    {
        $this->roles = Role::with('permissions')
            ->orderBy('name')
            ->get();
    }

    public function loadPermissions()
    {
        $this->permissions = Permission::orderBy('name')->get();

        $this->groupedPermissions = $this->permissions
            ->groupBy(fn($p) => explode('.', $p->name)[0] ?? 'general')
            ->sortKeys()
            ->map(fn($group) => $group->sortBy('name')->values())
            ->toArray();
    }

    public function resetForm()
    {
        $this->reset(['name', 'selectedPermissions']);
    }

    public function create()
    {
        $this->resetForm();
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        $normalized = strtolower(str_replace(' ', '.', trim($this->name)));

        $role = Role::create([
            'name' => $normalized,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->selectedPermissions);

        $this->resetForm();
        $this->loadRoles();

        $this->modal('create-role')->close();
        $this->dispatch('flash', type: 'success', message: 'Role created.');
    }

    public function edit($roleId)
    {
        $this->resetForm();

        $role = Role::with('permissions')->findOrFail($roleId);

        $this->name = $role->name;

        $this->selectedPermissions = $role->permissions
            ->pluck('name')
            ->values()
            ->toArray();
    }

    public function update($roleId)
    {
        $role = Role::findOrFail($roleId);

        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        $normalized = strtolower(str_replace(' ', '.', trim($this->name)));

        $role->update([
            'name' => $normalized,
        ]);

        $role->syncPermissions($this->selectedPermissions);

        $this->loadRoles();
        $this->resetForm();

        $this->modal("edit-role-{$roleId}")->close();
        $this->dispatch('flash', type: 'success', message: 'Role updated.');
    }

    public function delete($roleId)
    {
        $role = Role::findOrFail($roleId);

        if ($role->users()->count() > 0) {
            $this->dispatch('flash', type: 'error', message: 'Role is assigned to users.');
            return;
        }

        $role->delete();

        $this->loadRoles();

        $this->modal("delete-role-{$roleId}")->close();
        $this->dispatch('flash', type: 'info', message: 'Role deleted.');
    }

    public function togglePermission($permissionName)
    {
        if (in_array($permissionName, $this->selectedPermissions)) {
            $this->selectedPermissions = array_values(
                array_filter($this->selectedPermissions, fn($p) => $p !== $permissionName)
            );
        } else {
            $this->selectedPermissions[] = $permissionName;
        }
    }

    public function render()
    {
        return view('livewire.access.roles-index');
    }
}
