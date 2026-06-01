<div class="space-y-4">
    @forelse ($events as $event)
        <a href="{{ route('events.show', $event->id) }}"
            class="block rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 truncate">
                        {{ $event->title }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        @if ($event->start_datetime)
                            <flux:icon.calendar class="inline w-3.5 h-3.5 -mt-0.5" />
                            {{ $event->start_datetime->format('M d, Y g:i A') }}
                        @endif
                        @if ($event->location)
                            · <flux:icon.map-pin class="inline w-3.5 h-3.5 -mt-0.5" /> {{ $event->location }}
                        @endif
                    </p>
                    @if ($event->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-2">
                            {{ \Illuminate\Support\Str::limit($event->description, 140) }}
                        </p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    @php($mine = $myResponses[$event->id] ?? null)
                    @if ($mine === 'attending')
                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Attending</span>
                    @elseif ($mine === 'not_attending')
                        <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-600">Not attending</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-zinc-200 text-zinc-600">No response</span>
                    @endif
                    <p class="text-[11px] text-gray-400 mt-2">{{ $event->attending_count }} attending</p>
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center text-gray-500">
            No events right now. Check back soon.
        </div>
    @endforelse
</div>
