<?php

namespace App\Livewire\Access;

use Livewire\Component;
use Spatie\Permission\Models\Permission;

class PermissionsIndex extends Component
{
    public $permissions = [];
    public $groupedPermissions = [];

    public $name = '';

    public $selectedId = null;
    public $selectedName = '';

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

    public function confirmDelete($id, $name)
    {
        $this->selectedId = $id;
        $this->selectedName = $name;

        $this->modal('delete-permission')->show();
    }

    public function delete()
    {
        if (!$this->selectedId) {
            return;
        }

        Permission::findOrFail($this->selectedId)->delete();

        $this->reset(['selectedId', 'selectedName']);

        $this->loadPermissions();
        $this->modal('delete-permission')->close();
        $this->dispatch('flash', type: 'info', message: 'Permission deleted.');
    }

    public function store()
    {
        $this->validate();

        $normalized = strtolower(str_replace(' ', '.', trim($this->name)));

        Permission::create([
            'name' => $normalized,
            'guard_name' => 'web',
        ]);

        $this->reset('name');

        $this->loadPermissions();

        $this->modal('create-permission')->close();
        $this->dispatch('flash', type: 'success', message: 'Permission created.');
    }

    public function render()
    {
        return view('livewire.access.permissions-index');
    }
}
