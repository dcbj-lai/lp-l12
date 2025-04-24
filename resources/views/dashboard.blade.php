<x-layouts.app title="Dashboard">
    <div class="flex h-full w-full flex-1 flex-col rounded-xl">
        <div class="grid auto-rows-min md:grid-cols-4 gap-4">
            <!-- Bible Verse Card -->
            <livewire:feature-verse />
            <!-- Countdown Card -->
            <livewire:countdown-card />
            <livewire:steps-leaderboard />

        </div>
    </div>
</x-layouts.app>


<!-- Card Styling -->
<style>
    .dashboard-card {
        position: relative;
        color: #1a202c;
        background-color: oklch(0.985 0 0);
        /* Soft yellow */
        border-radius: 0.75rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        min-height: 150px;
        transition: all 0.3s ease-in-out;
        overflow: hidden;
    }

    /* Dark mode adaptation */
    .dark .dashboard-card {
        color: #e2e8f0;
        background-color: oklch(0.371 0 0);
        border-color: #4a5568;
    }

    /* Hover effect */
    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        filter: brightness(1.05);
    }
</style>
