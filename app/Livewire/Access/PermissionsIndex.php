<?php

namespace App\Livewire\Access;

use Livewire\Component;
use Spatie\Permission\Models\Permission;

class PermissionsIndex extends Component
{
    public $permissions = [];
    public $groupedPermissions = [];

    public $name = '';
    public $showModal = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:permissions,name',
    ];

    public function mount()
    {
        $this->loadPermissions();
    }

    public function loadPermissions()
    {
        $this->permissions = Permission::orderBy('name')->get();

        $this->groupedPermissions = $this->permissions
            ->groupBy(fn($p) => explode('.', $p->name)[0] ?? 'general')
            ->toArray();
    }

    public function create()
    {
        $this->reset('name');
        $this->showModal = true;
    }

    public function store()
    {
        $this->validate();

        $normalized = strtolower(str_replace(' ', '.', trim($this->name)));

        Permission::create([
            'name' => $normalized,
            'guard_name' => 'web',
        ]);

        // ✅ Reset input so placeholder shows again
        $this->reset('name');

        $this->loadPermissions();
        $this->modal('create-permission')->close();
        $this->dispatch('flash', type: 'success', message: 'Permission created.');
    }

    public function delete($id)
    {
        Permission::findOrFail($id)->delete();

        $this->loadPermissions();

        // session()->flash('message', 'Permission deleted.');
        $this->dispatch('flash', type: 'info', message: 'Permission deleted.');
    }

    public function render()
    {
        return view('livewire.access.permissions-index');
    }
}
