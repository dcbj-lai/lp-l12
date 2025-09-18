<div x-data="{ show: true, menuOpen: false }" x-show="show"
    class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800 flex flex-col justify-center aspect-square">

    <!-- Verse Content -->
    <div
        class="text-center text-transparent bg-gradient-to-r from-sky-500 via-blue-500 to-orange-500 bg-clip-text font-semibold">
        <p class="text-lg">{{ $verse }}</p>
        <p class="mt-2 text-sm font-semibold text-gray-400">{{ $reference }}</p>
    </div>
</div>
