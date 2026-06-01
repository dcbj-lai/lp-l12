<x-dashboard-card class="p-4">
    <div class="relative flex flex-col aspect-square">

        <!-- Title -->
        <div class="absolute top-4 left-4 right-4">
            <h2 class="text-sm font-semibold text-gray-400 flex items-center gap-1">
                <flux:icon.user-check class="w-4 h-4" />
                Registered Users
            </h2>
            @if ($event)
                <p class="text-[11px] text-gray-500 truncate mt-0.5">{{ $event->title }}</p>
            @endif
        </div>

        <!-- Content -->
        <div class="flex flex-col justify-center items-center flex-grow px-4 text-center pt-10">
            @if (!$event)
                <p class="text-gray-500 text-xs">No events yet.</p>
            @elseif ($registrants->isEmpty())
                <p class="text-gray-500 text-xs">No one has registered yet.</p>
            @else
                <ul class="space-y-3 w-full max-w-xs text-sm">
                    @foreach ($registrants as $index => $reg)
                        <li class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-gray-500 text-xs w-4">{{ $index + 1 }}.</span>
                                <div class="h-7 w-7 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center shrink-0">
                                    @if ($reg->user?->profile_photo_path)
                                        <img src="{{ Storage::disk('s3')->url($reg->user->profile_photo_path) }}" class="h-full w-full object-cover">
                                    @else
                                        <span class="text-[10px] font-semibold text-black dark:text-white">{{ $reg->user?->initials() }}</span>
                                    @endif
                                </div>
                                <span class="text-gray-300 font-medium truncate">
                                    {{ $reg->user?->preferred_name ?? $reg->user?->name }}
                                </span>
                            </div>
                            @if ($reg->guest_count)
                                <span class="text-[11px] text-gray-500 shrink-0">+{{ $reg->guest_count }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if ($attendingCount > $registrants->count())
                    <p class="text-[11px] text-gray-500 mt-3">
                        +{{ $attendingCount - $registrants->count() }} more attending
                    </p>
                @endif
            @endif
        </div>

        <!-- Footer -->
        @if ($event)
            <div class="absolute bottom-4 right-4">
                <flux:button size="xs" variant="ghost" href="{{ route('events.registrants', $event->id) }}" wire:navigate>
                    See more...
                </flux:button>
            </div>
        @endif
    </div>
</x-dashboard-card>
