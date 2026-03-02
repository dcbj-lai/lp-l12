{{-- CARD MODE (Dashboard) --}}
@if ($mode === 'card')

    <div x-data="{ show: true }" x-show="show"
        class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800
                relative flex flex-col aspect-square">

        {{-- Close Button --}}
        <button @click="show = false"
            class="absolute top-2 right-2 text-orange-400 opacity-50 hover:opacity-100 hover:text-orange-600 dark:hover:text-orange-300 transition-opacity">
            &times;
        </button>

        {{-- Title (Top Left Fixed) --}}
        <div class="absolute top-4 left-4">
            <h2 class="text-md font-semibold text-gray-400 dark:text-gray-400">
                <flux:icon.footprints class="w-5 h-5 inline text-gray-400" />
                Top Steppers ({{ \Carbon\Carbon::parse($startDate)->format('F') }})
            </h2>
        </div>

        {{-- Centered Content --}}
        <div class="flex flex-col justify-center items-center flex-grow px-4 text-center">

            @if ($leaders->isEmpty())
                <p class="text-gray-600 dark:text-gray-300 text-xs">
                    No steps logged this month yet.
                </p>
            @else
                <ul class="space-y-3 w-full max-w-xs">
                    @foreach ($leaders as $index => $entry)
                        <li class="flex justify-between items-center text-sm">

                            {{-- Name --}}
                            <span class="text-gray-400 font-medium tracking-wide">
                                {{ $index + 1 }}. {{ $entry->user->name }}
                            </span>

                            {{-- Steps --}}
                            <span class="text-blue-600 font-semibold dark:text-blue-400">
                                {{ number_format($entry->total_steps) }}
                            </span>

                            {{-- Badge --}}
                            <span>
                                @if ($index === 0)
                                    <flux:icon name="trophy" class="text-yellow-500 h-5 w-5" />
                                @elseif($index === 1)
                                    <flux:icon name="medal" class="text-gray-400 h-5 w-5" />
                                @elseif($index === 2)
                                    <flux:icon name="award" class="text-orange-500 h-5 w-5" />
                                @endif
                            </span>

                        </li>
                    @endforeach
                </ul>
            @endif

        </div>

        {{-- Footer Button --}}
        <div class="absolute bottom-4 right-4">
            <flux:button size="xs" variant="ghost" href="{{ route('steps.index') }}">
                See all...
            </flux:button>
        </div>

    </div>

    {{-- FULL MODE (Leaderboard Page) --}}
@else
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">

        <h2 class="text-xl font-bold mb-4">
            <flux:icon.footprints class="w-6 h-6 inline" />
            Steps Leaderboard
        </h2>

        {{-- Date Filters --}}
        <div class="flex flex-col md:flex-row gap-4 mb-6">

            <div>
                <label class="block text-sm font-medium mb-1">From</label>
                <input type="date" wire:model.live="startDate" class="border rounded p-2 w-full">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">To</label>
                <input type="date" wire:model.live="endDate" class="border rounded p-2 w-full">
            </div>

        </div>

        {{-- Leaderboard Table --}}
        @if ($leaders->isEmpty())
            <p class="text-gray-600 dark:text-gray-300 text-sm">
                No steps found for selected period.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left p-2">Rank</th>
                            <th class="text-left p-2">Name</th>
                            <th class="text-left p-2">Total Steps</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaders as $index => $entry)
                            <tr class="border-b hover:bg-gray-100 dark:hover:bg-gray-700 transition">

                                {{-- Rank with Badge --}}
                                <td class="p-2 font-semibold">
                                    <div class="flex items-center gap-2">

                                        @if ($index === 0)
                                            <flux:icon name="trophy" class="text-yellow-500 h-5 w-5" />
                                        @elseif($index === 1)
                                            <flux:icon name="medal" class="text-gray-400 h-5 w-5" />
                                        @elseif($index === 2)
                                            <flux:icon name="award" class="text-orange-500 h-5 w-5" />
                                        @endif

                                        <span>{{ $index + 1 }}</span>
                                    </div>
                                </td>

                                {{-- Name --}}
                                <td class="p-2">
                                    {{ $entry->user->name }}
                                </td>

                                {{-- Steps --}}
                                <td class="p-2 font-bold text-blue-600 dark:text-blue-400">
                                    {{ number_format($entry->total_steps) }}
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>

@endif
