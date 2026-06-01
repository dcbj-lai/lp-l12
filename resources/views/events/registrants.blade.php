<x-layouts.app title="Registered — {{ $event->title }}">
    <div class="p-4 md:p-6 max-w-3xl mx-auto space-y-6">

        <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('events.show', $event->id) }}">
            Back to Event
        </flux:button>

        <div>
            <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">Registered Users</h1>
            <p class="text-sm text-gray-500">{{ $event->title }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900 text-left text-gray-500">
                    <tr>
                        <th class="py-2 px-4 w-10">#</th>
                        <th class="py-2 px-4">Name</th>
                        <th class="py-2 px-4">Response</th>
                        <th class="py-2 px-4 text-center">Guests</th>
                        <th class="py-2 px-4">Responded</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $i => $reg)
                        <tr class="border-t border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 px-4 text-gray-400">{{ $i + 1 }}</td>
                            <td class="py-2 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center shrink-0">
                                        @if ($reg->user?->profile_photo_path)
                                            <img src="{{ Storage::disk('s3')->url($reg->user->profile_photo_path) }}" class="h-full w-full object-cover">
                                        @else
                                            <span class="text-xs font-semibold text-black dark:text-white">{{ $reg->user?->initials() }}</span>
                                        @endif
                                    </div>
                                    <span class="text-zinc-800 dark:text-zinc-200">
                                        {{ $reg->user?->preferred_name ?? $reg->user?->name }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-2 px-4">
                                @if ($reg->status === 'attending')
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Attending</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-600">Not attending</span>
                                @endif
                            </td>
                            <td class="py-2 px-4 text-center">{{ $reg->guest_count }}</td>
                            <td class="py-2 px-4 text-gray-500">{{ optional($reg->responded_at)->format('M d, g:i A') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">No one has responded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
