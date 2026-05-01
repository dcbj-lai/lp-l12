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

    public $editingRoleId = null;

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
        $this->reset(['name', 'selectedPermissions', 'editingRoleId']);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->modal('role-modal')->show();
    }

    public function openEditModal($roleId)
    {
        $this->resetForm();

        $role = Role::with('permissions')->findOrFail($roleId);

        $this->editingRoleId = $role->id;
        $this->name = $role->name;

        $this->selectedPermissions = $role->permissions
            ->pluck('name')
            ->toArray();

        $this->modal('role-modal')->show();
    }

    public function save()
    {
        if ($this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);

            $this->validate([
                'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            ]);

            $normalized = strtolower(str_replace(' ', '.', trim($this->name)));

            $role->update([
                'name' => $normalized,
            ]);

            $role->syncPermissions($this->selectedPermissions);

            $message = 'Role updated.';
        } else {
            $this->validate([
                'name' => 'required|string|max:255|unique:roles,name',
            ]);

            $normalized = strtolower(str_replace(' ', '.', trim($this->name)));

            $role = Role::create([
                'name' => $normalized,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($this->selectedPermissions);

            $message = 'Role created.';
        }

        $this->loadRoles();
        $this->resetForm();

        $this->modal('role-modal')->close();

        $this->dispatch('flash', type: 'success', message: $message);
    }

    public function confirmDelete($roleId)
    {
        $this->editingRoleId = $roleId;
        $this->modal('delete-modal')->show();
    }

    public function delete()
    {
        $role = Role::findOrFail($this->editingRoleId);

        if ($role->users()->count() > 0) {
            $this->dispatch('flash', type: 'error', message: 'Role is assigned to users.');
            return;
        }

        $role->delete();

        $this->loadRoles();
        $this->resetForm();

        $this->modal('delete-modal')->close();

        $this->dispatch('flash', type: 'info', message: 'Role deleted.');
    }

    public function render()
    {
        return view('livewire.access.roles-index');
    }
}
