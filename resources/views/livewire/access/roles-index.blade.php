<div class="p-4 space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
            Roles
        </h1>

        <flux:button variant="primary" wire:click="openCreateModal">
            + Add Role
        </flux:button>
    </div>

    <!-- Roles List -->
    <div class="space-y-3">
        @foreach ($roles as $role)
            <div wire:key="role-{{ $role->id }}"
                class="flex items-center justify-between bg-white dark:bg-zinc-900 p-4 rounded-xl">

                <div>
                    <div class="font-medium text-zinc-800 dark:text-zinc-100">
                        {{ $role->name }}
                    </div>
                    <div class="text-xs text-zinc-500">
                        {{ $role->permissions->count() }} permissions
                    </div>
                </div>

                <div class="flex items-center gap-2">

                    <flux:button variant="ghost" size="sm" icon="pencil-square"
                        wire:click="openEditModal({{ $role->id }})" />

                    <flux:button variant="ghost" size="sm" icon="circle-x" class="text-red-500"
                        wire:click="confirmDelete({{ $role->id }})" />

                </div>
            </div>
        @endforeach
    </div>

    <!-- CREATE / EDIT MODAL -->
    <flux:modal name="role-modal">
        <div class="p-6 space-y-4">

            <h2 class="text-lg font-semibold">
                {{ $editingRoleId ? 'Edit Role' : 'Create Role' }}
            </h2>

            <input wire:model="name" placeholder="e.g. editor" class="w-full px-3 py-2 border rounded-lg" />

            @error('name')
                <div class="text-red-500 text-sm">{{ $message }}</div>
            @enderror

            <div class="space-y-3 max-h-64 overflow-y-auto">
                @foreach ($groupedPermissions as $group => $permissions)
                    <div>
                        <div class="text-xs font-semibold uppercase text-zinc-500">
                            {{ $group }}
                        </div>

                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" value="{{ $permission['name'] }}"
                                    wire:model="selectedPermissions">
                                {{ $permission['name'] }}
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('role-modal').close()">
                    Cancel
                </flux:button>

                <flux:button wire:click="save">
                    Save
                </flux:button>
            </div>

        </div>
    </flux:modal>

    <!-- DELETE MODAL -->
    <flux:modal name="delete-modal">
        <div class="p-6 space-y-4">

            <h2 class="text-lg font-semibold">Delete Role</h2>

            <p>Are you sure you want to delete this role?</p>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" x-on:click="$flux.modal('delete-modal').close()">
                    Cancel
                </flux:button>

                <flux:button variant="danger" wire:click="delete">
                    Delete
                </flux:button>
            </div>

        </div>
    </flux:modal>

</div>
