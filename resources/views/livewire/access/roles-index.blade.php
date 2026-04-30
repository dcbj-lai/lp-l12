<div class="p-4 space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
            Roles
        </h1>

        <flux:modal.trigger name="create-role">
            <flux:button variant="primary">+ Add Role</flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Roles List -->
    <div class="space-y-3">
        @foreach ($roles as $role)
            <div class="flex items-center justify-between bg-white dark:bg-zinc-900 p-4 rounded-xl">

                <div>
                    <div class="font-medium text-zinc-800 dark:text-zinc-100">
                        {{ $role->name }}
                    </div>
                    <div class="text-xs text-zinc-500">
                        {{ $role->permissions->count() }} permissions
                    </div>
                </div>

                <div class="flex items-center gap-2">

                    <!-- Edit -->
                    <flux:modal.trigger name="edit-role-{{ $role->id }}">
                        <flux:button variant="ghost" size="sm" icon="pencil-square"
                            wire:click="edit({{ $role->id }})" />
                    </flux:modal.trigger>

                    <!-- Delete -->
                    <flux:modal.trigger name="delete-role-{{ $role->id }}">
                        <flux:button variant="ghost" icon="circle-x" size="sm"
                            class="text-red-500 hover:text-red-700" />
                    </flux:modal.trigger>

                </div>
            </div>

            <!-- EDIT MODAL -->
            <flux:modal name="edit-role-{{ $role->id }}" wire:key="edit-role-modal-{{ $role->id }}">
                <div class="p-6 space-y-4">

                    <h2 class="text-lg font-semibold">Edit Role</h2>

                    <input wire:model.defer="name" class="w-full px-3 py-2 border rounded-lg" />

                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        @foreach ($groupedPermissions as $group => $permissions)
                            <div>
                                <div class="text-xs font-semibold uppercase text-zinc-500">
                                    {{ $group }}
                                </div>

                                @foreach ($permissions as $permission)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" value="{{ $permission['name'] }}"
                                            wire:model.live="selectedPermissions">
                                        {{ $permission['name'] }}
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:button variant="ghost" x-on:click="$flux.modal('edit-role-{{ $role->id }}').close()">
                            Cancel</flux:button>
                        <flux:button wire:click="update({{ $role->id }})">Save</flux:button>
                    </div>

                </div>
            </flux:modal>

            <!-- DELETE MODAL -->
            <flux:modal name="delete-role-{{ $role->id }}">
                <div class="p-6 space-y-4">
                    <h2 class="text-lg font-semibold">Delete Role</h2>

                    <p>Delete <strong>{{ $role->name }}</strong>?</p>

                    <div class="flex justify-end gap-2">
                        <flux:button variant="ghost"
                            x-on:click="$flux.modal('delete-role-{{ $role->id }}').close()">
                            Cancel
                        </flux:button>
                        <flux:button variant="danger" wire:click="delete({{ $role->id }})">
                            Delete
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @endforeach
    </div>

    <!-- CREATE MODAL -->
    <flux:modal name="create-role">
        <div class="p-6 space-y-4">

            <h2 class="text-lg font-semibold">Create Role</h2>

            <input wire:model.defer="name" placeholder="e.g. editor" class="w-full px-3 py-2 border rounded-lg" />

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
                <flux:button variant="ghost" x-on:click="$flux.modal('create-role').close()">Cancel</flux:button>
                <flux:button wire:click="store">Save</flux:button>
            </div>

        </div>
    </flux:modal>

</div>
