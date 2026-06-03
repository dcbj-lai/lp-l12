<x-dashboard-card class="p-4">
    <div class="relative flex flex-col aspect-square">

        <!-- Title -->
        <div class="absolute top-4 left-4">
            <h2 class="text-sm font-semibold text-gray-400 flex items-center gap-1">
                <flux:icon.calendar-days class="w-4 h-4" />
                Events
            </h2>
        </div>

        <!-- Content -->
        <div class="flex flex-col justify-center flex-grow px-1 pt-10">
            @if ($events->isEmpty())
                <p class="text-gray-500 text-xs text-center">No events right now.</p>
            @else
                <ul class="space-y-2 w-full text-sm">
                    @foreach ($events as $event)
                        <li>
                            <a href="{{ route('events.show', $event->id) }}" wire:navigate
                                class="block rounded-lg px-2 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700/60 transition">
                                <div class="flex items-center gap-2 min-w-0">
                                    <flux:icon.calendar-days class="w-4 h-4 text-blue-400 shrink-0" />
                                    <span class="text-gray-300 font-medium truncate">{{ $event->title }}</span>
                                </div>
                                @if ($eventDateRange = $event->formattedDateRange(false))
                                    <span class="block truncate text-[11px] text-gray-500 ml-6">
                                        {{ $eventDateRange }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <!-- Footer -->
        <div class="absolute bottom-4 right-4">
            <flux:button size="xs" variant="ghost" href="{{ route('events.index') }}" wire:navigate>
                See all
            </flux:button>
        </div>
    </div>
</x-dashboard-card>
