<x-layouts.app title="Users">

    <div class="space-y-4">

        <!-- Header -->
        <div>
            <h1 class="text-xl md:text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
                Users
            </h1>
            <p class="text-sm text-gray-500">
                Assign roles and view permissions
            </p>
        </div>

        <!-- Livewire Component -->
        <livewire:access.users-index />

    </div>

</x-layouts.app>
