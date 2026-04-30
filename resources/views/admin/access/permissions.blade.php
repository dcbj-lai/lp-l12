<x-layouts.app title="Permissions">

    <div class="space-y-4">

        <!-- Header -->
        <div>
            <h1 class="text-xl md:text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
                Permissions
            </h1>
            <p class="text-sm text-gray-500">
                Define system capabilities and actions
            </p>
        </div>

        <!-- Livewire Component -->
        <livewire:access.permissions-index />

    </div>

</x-layouts.app>
