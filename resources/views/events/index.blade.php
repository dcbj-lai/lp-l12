<x-layouts.app title="Events">
    <div class="p-4 md:p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">Events</h1>
                <p class="text-sm text-gray-500">Events from People &amp; Culture / Human Resources.</p>
            </div>
            <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('dashboard') }}">
                Dashboard
            </flux:button>
        </div>

        <livewire:events.event-list />
    </div>
</x-layouts.app>
