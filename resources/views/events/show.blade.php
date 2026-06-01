<x-layouts.app title="{{ $event->title }}">
    <div class="p-4 md:p-6 max-w-4xl mx-auto space-y-6">

        <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('events.index') }}">
            Back to Events
        </flux:button>

        {{-- Event details --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 space-y-4">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">{{ $event->title }}</h1>
                <span class="text-xs px-2 py-0.5 rounded-full shrink-0
                    {{ $event->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-600' }}">
                    {{ ucfirst($event->status) }}
                </span>
            </div>

            <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500">
                @if ($event->start_datetime)
                    <span><flux:icon.calendar class="inline w-4 h-4 -mt-0.5" />
                        {{ $event->start_datetime->format('M d, Y g:i A') }}
                        @if ($event->end_datetime) – {{ $event->end_datetime->format('g:i A') }} @endif
                    </span>
                @endif
                @if ($event->location)
                    <span><flux:icon.map-pin class="inline w-4 h-4 -mt-0.5" /> {{ $event->location }}</span>
                @endif
                <span><flux:icon.users class="inline w-4 h-4 -mt-0.5" /> {{ $attendingCount }} attending</span>
            </div>

            @if ($event->rsvp_deadline)
                <p class="text-xs text-amber-600 dark:text-amber-400">
                    RSVP by {{ $event->rsvp_deadline->format('M d, Y g:i A') }}
                </p>
            @endif

            @if ($event->description)
                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ $event->description }}
                </div>
            @endif

            {{-- Instruction attachments --}}
            @if ($event->attachments->isNotEmpty())
                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 mb-2">
                        Instructions &amp; Files
                    </h3>
                    <ul class="space-y-1">
                        @foreach ($event->attachments as $att)
                            <li>
                                <a href="{{ route('events.attachment', $att->id) }}" target="_blank"
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                                    <flux:icon.paperclip class="w-4 h-4" />
                                    {{ $att->original_name ?? basename($att->file_path) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- RSVP toggle (staff registration via existing profile) --}}
        <livewire:events.attend-toggle :event="$event" />

        <p class="text-xs text-gray-400">
            Your profile details (contact, dietary, emergency contact) are shared with the organizers when you RSVP.
            Keep them current in <a href="/settings/profile-2" class="underline">Settings</a>.
        </p>
    </div>
</x-layouts.app>
