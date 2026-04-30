<div class="p-4 space-y-4">

    <!-- Header -->
    <h1 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
        Users
    </h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- USERS LIST -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl p-4 space-y-2 max-h-80 overflow-y-auto">

            <h2 class="text-sm font-semibold text-zinc-500 sticky top-0 bg-white dark:bg-zinc-900 pb-2">
                Users
            </h2>

            @foreach ($users as $user)
                <div wire:click="selectUser({{ $user->id }})"
                    class="p-2 rounded cursor-pointer text-sm
            {{ $selectedUserId === $user->id
                ? 'bg-lime-100 text-lime-700 ring-1 ring-lime-300 shadow-sm
                   dark:bg-lime-400/20 dark:text-lime-200 dark:ring-lime-400/40 dark:shadow-[0_0_10px_rgba(163,230,53,0.25)]'
                : 'hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                    {{ $user->name }}
                </div>
            @endforeach

        </div>

        <!-- RIGHT PANEL -->
        <div class="md:col-span-2 space-y-4">

            @if ($this->selectedUser)

                <!-- ROLES STACK -->
                <div class="bg-white dark:bg-zinc-900 rounded-xl p-4 space-y-3 max-h-64 overflow-y-auto">

                    <h2 class="text-sm font-semibold text-zinc-500 sticky top-0 bg-white dark:bg-zinc-900 pb-2">
                        Roles
                    </h2>

                    @foreach ($roles as $role)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" value="{{ $role->name }}"
                                wire:click="toggleRole('{{ $role->name }}')" @checked(in_array($role->name, $selectedRoles))>
                            <span>{{ $role->name }}</span>
                        </label>
                    @endforeach

                </div>

                <!-- PERMISSIONS STACK -->
                <div class="bg-white dark:bg-zinc-900 rounded-xl p-4 space-y-3 max-h-64 overflow-y-auto">

                    <h2 class="text-sm font-semibold text-zinc-500 sticky top-0 bg-white dark:bg-zinc-900 pb-2">
                        Permissions
                    </h2>

                    @php
                        $grouped = collect($this->derivedPermissions)->groupBy(
                            fn($p) => explode('.', $p)[0] ?? 'general',
                        );
                    @endphp

                    @foreach ($grouped as $group => $permissions)
                        <div>
                            <div class="text-xs uppercase text-zinc-500 font-semibold">
                                {{ $group }}
                            </div>

                            @foreach ($permissions as $permission)
                                <div class="text-sm text-zinc-700 dark:text-zinc-200">
                                    ✓ {{ $permission }}
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                </div>
            @else
                <div class="text-sm text-zinc-500">
                    Select a user to manage roles.
                </div>
            @endif

        </div>

    </div>

</div>
