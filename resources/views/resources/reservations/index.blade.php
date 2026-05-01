<x-layouts.app title="Reservations">

    <div class="space-y-4">

        <!-- Header -->
        <div>
            <h1 class="text-xl md:text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
                Resource Reservations
            </h1>
            <p class="text-sm text-gray-500">
                Approval dashboard
            </p>
        </div>

        <!-- Livewire Component -->
        <livewire:resources.reservation-index />

    </div>

</x-layouts.app>
