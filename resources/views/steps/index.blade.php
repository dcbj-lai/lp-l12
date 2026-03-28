<x-layouts.app title="Steps">

    <div class="space-y-4">

        <!-- Header -->
        <div>
            <h1 class="text-xl md:text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
                Steps
            </h1>
            <p class="text-sm text-gray-500">
                Leaderboard
            </p>
        </div>

        <!-- Leaderboard -->
        <livewire:steps-leaderboard mode="full" />

    </div>

</x-layouts.app>
