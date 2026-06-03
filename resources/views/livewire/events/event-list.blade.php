<div class="space-y-4">
    @forelse ($events as $event)
        <article
            class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:shadow-md transition">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('events.show', $event->id) }}" wire:navigate
                        class="block text-base font-semibold text-zinc-800 dark:text-zinc-100 truncate hover:text-blue-600 dark:hover:text-blue-400">
                        {{ $event->title }}
                    </a>
                    <p class="text-xs text-gray-500 mt-1">
                        @if ($eventDateRange = $event->formattedDateRange())
                            <flux:icon.calendar class="inline w-3.5 h-3.5 -mt-0.5" />
                            {{ $eventDateRange }}
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
                <div class="w-full shrink-0 rounded-lg border border-zinc-200 bg-zinc-50/70 p-3 dark:border-zinc-700 dark:bg-zinc-900/40 md:w-auto md:min-w-72">
                    @php($mine = $myResponses[$event->id] ?? null)
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-200 pb-2 dark:border-zinc-700">
                        <span class="text-[11px] font-medium uppercase text-zinc-500 dark:text-zinc-400">Your RSVP</span>
                        @if ($mine === 'attending')
                            <span class="cursor-default select-none rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-400/40 dark:bg-emerald-950/40 dark:text-emerald-300">Attending</span>
                        @elseif ($mine === 'not_attending')
                            <span class="cursor-default select-none rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 dark:border-red-400/40 dark:bg-red-950/40 dark:text-red-300">Not attending</span>
                        @else
                            <span class="cursor-default select-none rounded-full border border-zinc-300 bg-white px-2.5 py-1 text-xs font-semibold text-zinc-600 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">No response</span>
                        @endif
                    </div>

                    <div class="mt-3 flex flex-col gap-2 sm:flex-row md:justify-end">
                        <flux:button class="w-full justify-center border border-blue-700 dark:border-blue-400 sm:w-auto" size="xs" variant="primary" icon="calendar"
                            href="{{ route('events.show', $event->id) }}" wire:navigate>
                            RSVP
                        </flux:button>
                        <flux:button class="w-full justify-center border border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-400/60 dark:text-amber-300 dark:hover:bg-amber-950/40 sm:w-auto" size="xs" variant="ghost" icon="user-check"
                            href="{{ route('events.registrants', $event->id) }}" wire:navigate>
                            Who else signed up
                        </flux:button>
                    </div>

                    <p class="mt-3 text-right text-[11px] text-gray-400">{{ $event->attending_count }} attending</p>
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center text-gray-500">
            No events right now. Check back soon.
        </div>
    @endforelse
</div>
