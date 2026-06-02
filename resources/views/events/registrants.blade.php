<x-layouts.app title="Who else signed up - {{ $event->title }}">
    <div class="p-4 md:p-6 max-w-5xl mx-auto space-y-6">
        @php($customFieldLabels = $event->customFieldLabels())

        <flux:button size="sm" variant="ghost" icon="arrow-left" href="{{ route('events.show', $event->id) }}">
            Back to Event
        </flux:button>

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">Who else signed up</h1>
                <p class="text-sm text-gray-500">{{ $event->title }}</p>
            </div>

            @can('events.manage')
                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" icon="download"
                        href="{{ route('events.registrants.csv', $event->id) }}">CSV</flux:button>
                    <flux:button size="sm" variant="ghost" icon="download"
                        href="{{ route('events.registrants.pdf', $event->id) }}">PDF</flux:button>
                </div>
            @endcan
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900 text-left text-gray-500">
                    <tr>
                        <th class="py-2 px-4 w-10">#</th>
                        <th class="py-2 px-4">Name</th>
                        <th class="py-2 px-4 text-center">Guests</th>
                        @foreach ($customFieldLabels as $label)
                            <th class="py-2 px-4">{{ $label }}</th>
                        @endforeach
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
                            <td class="py-2 px-4 text-center">{{ $reg->guest_count }}</td>
                            @foreach ($customFieldLabels as $fieldIndex => $label)
                                <td class="py-2 px-4 text-gray-500">
                                    {{ $reg->customFieldAnswer((int) $fieldIndex) ?: '-' }}
                                </td>
                            @endforeach
                            <td class="py-2 px-4 text-gray-500">{{ optional($reg->responded_at)->format('M d, g:i A') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + count($customFieldLabels) }}" class="py-6 text-center text-gray-500">
                                No one has signed up yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
