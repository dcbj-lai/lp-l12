<x-dashboard-card class="p-4 flex items-center justify-center">
    <!-- Verse Content -->
    <div
        class="text-center text-transparent bg-gradient-to-r from-sky-500 via-blue-500 to-orange-500 bg-clip-text font-semibold">

        <p class="text-lg">{{ $verse }}</p>
        <p class="mt-2 text-sm font-semibold text-gray-400">
            {{ $reference }}
        </p>

    </div>
</x-dashboard-card>
