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
    public $selectedUser = null;
    public $selectedRoles = [];

    public $search = '';

    public function mount()
    {
        $this->loadUsers();
        $this->loadRoles();
    }

    public function loadUsers()
    {
        $this->users = User::query()
            ->when($this->search, function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%']);
            })
            ->orderBy('name')
            ->get();
    }

    public function updatedSearch()
    {
        $this->loadUsers();
    }

    public function loadRoles()
    {
        $this->roles = Role::orderBy('name')->get();
    }

    public function selectUser($userId)
    {
        // prevent unnecessary reload
        if ($this->selectedUserId === $userId)
            return;

        $this->selectedUserId = $userId;

        // 🔥 always hydrate fresh
        $this->selectedUser = User::with('roles.permissions')->findOrFail($userId);

        $this->selectedRoles = $this->selectedUser->roles
            ->pluck('name')
            ->values()
            ->toArray();
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
        if (!$this->selectedUser)
            return;

        // 🔥 persist
        $this->selectedUser->syncRoles($this->selectedRoles);

        // 🔥 FULL REFRESH (critical)
        $this->selectedUser = $this->selectedUser->fresh(['roles.permissions']);

        $this->selectedRoles = $this->selectedUser->roles
            ->pluck('name')
            ->values()
            ->toArray();

        $this->dispatch('flash', type: 'success', message: 'Roles updated.');
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
