<x-layouts.app title="Roles">

    <div class="space-y-4">

        <!-- Header -->
        <div>
            <h1 class="text-xl md:text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
                Roles
            </h1>
            <p class="text-sm text-gray-500">
                Group permissions into reusable roles
            </p>
        </div>

        <!-- Livewire Component -->
        <livewire:access.roles-index />

    </div>

</x-layouts.app>
