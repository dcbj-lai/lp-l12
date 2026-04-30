<?php

namespace App\Livewire\Access;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UsersIndex extends Component
{
    public $users = [];
    public $roles = [];

    public $selectedUserId = null;
    public $selectedRoles = [];

    public function mount()
    {
        $this->loadUsers();
        $this->loadRoles();
    }

    public function loadUsers()
    {
        $this->users = User::orderBy('name')->get();
    }

    public function loadRoles()
    {
        $this->roles = Role::orderBy('name')->get();
    }

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;

        $user = User::findOrFail($userId);

        $this->selectedRoles = $user->roles->pluck('name')->toArray();
    }

    public function toggleRole($roleName)
    {
        if (in_array($roleName, $this->selectedRoles)) {
            $this->selectedRoles = array_values(
                array_filter($this->selectedRoles, fn($r) => $r !== $roleName)
            );
        } else {
            $this->selectedRoles[] = $roleName;
        }

        $this->syncRoles();
    }

    public function syncRoles()
    {
        if (!$this->selectedUserId)
            return;

        $user = User::findOrFail($this->selectedUserId);

        $user->syncRoles($this->selectedRoles);

        $this->dispatch('flash', type: 'success', message: 'Roles updated.');
    }

    public function getSelectedUserProperty()
    {
        return $this->selectedUserId
            ? User::with('roles.permissions')->find($this->selectedUserId)
            : null;
    }

    public function getDerivedPermissionsProperty()
    {
        if (!$this->selectedUser)
            return collect();

        return $this->selectedUser
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();
    }

    public function render()
    {
        return view('livewire.access.users-index');
    }
}
