<div class="p-4 space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
            Permissions
        </h1>

        <flux:modal.trigger name="create-permission">
            <flux:button variant="primary" color="teal" size="sm" icon="plus">
                Add
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Flash -->
    @if (session()->has('message'))
        <div class="text-sm text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <!-- Permissions List -->
    <div class="space-y-4">
        @forelse ($groupedPermissions as $group => $permissions)
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-4 shadow">

                <!-- Group Title -->
                <h2 class="text-sm font-semibold uppercase text-zinc-500 mb-3">
                    {{ ucfirst($group) }}
                </h2>

                <div class="space-y-2">
                    @foreach ($permissions as $permission)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-700 dark:text-zinc-200">
                                {{ $permission['name'] }}
                            </span>

                            <flux:button variant="ghost" size="sm" icon="circle-x"
                                class="text-red-500 hover:text-red-700"
                                wire:click="confirmDelete({{ $permission['id'] }}, '{{ $permission['name'] }}')" />
                        </div>
                    @endforeach
                </div>

            </div>
        @empty
            <div class="text-sm text-zinc-500">
                No permissions found.
            </div>
        @endforelse
    </div>

    <!-- Delete Modal (SINGLE) -->
    <flux:modal name="delete-permission">

        <div class="p-6 space-y-4">
            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
                Delete Permission
            </h2>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Are you sure you want to delete
                <span class="font-semibold">{{ $selectedName }}</span>?
            </p>

            <div class="flex justify-end space-x-2">

                <flux:button variant="ghost" x-on:click="$dispatch('close-modal', { name: 'delete-permission' })">
                    Cancel
                </flux:button>

                <flux:button variant="danger" wire:click="delete">
                    Delete
                </flux:button>

            </div>
        </div>

    </flux:modal>

    <!-- Create Permission Modal -->
    <flux:modal name="create-permission">

        <div class="p-6 space-y-4">

            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
                Create Permission
            </h2>

            <input type="text" wire:model.defer="name" placeholder="user.create"
                class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-800 dark:border-zinc-700" />

            @error('name')
                <div class="text-red-500 text-sm">{{ $message }}</div>
            @enderror

            <div class="flex justify-end space-x-2">

                <flux:button variant="ghost" x-on:click="$dispatch('close-modal', { name: 'create-permission' })">
                    Cancel
                </flux:button>

                <flux:button variant="primary" wire:click="store">
                    Save
                </flux:button>

            </div>

        </div>

    </flux:modal>

</div>
