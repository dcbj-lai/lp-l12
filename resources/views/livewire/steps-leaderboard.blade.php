<div>

    {{-- CARD MODE --}}
    @if ($mode === 'card')

        <div x-data="{ show: true }" x-show="show"
            class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800 relative flex flex-col aspect-square">

            <button @click="show = false"
                class="absolute top-2 right-2 text-orange-400 opacity-50 hover:opacity-100 hover:text-orange-600 dark:hover:text-orange-300">
                &times;
            </button>

            <div class="absolute top-4 left-4">
                <h2 class="text-sm font-semibold text-gray-400">
                    <flux:icon.footprints class="w-4 h-4 inline text-gray-400" />
                    Top Steppers ({{ \Carbon\Carbon::parse($startDate)->format('F') }})
                </h2>
            </div>

            <div class="flex flex-col justify-center items-center flex-grow px-4 text-center">

                @if ($leaders->isEmpty())

                    <p class="text-gray-600 dark:text-gray-300 text-xs">
                        No steps logged this month yet.
                    </p>
                @else
                    <ul class="space-y-3 w-full max-w-xs text-sm">

                        @foreach ($leaders as $index => $entry)
                            <li class="flex justify-between items-center">

                                <span class="text-gray-400 font-medium tracking-wide">
                                    {{ $index + 1 }}. {{ $entry->user->name }}
                                </span>

                                <span class="text-blue-600 font-semibold dark:text-blue-400">
                                    {{ number_format($entry->total_steps) }}
                                </span>

                                <span>
                                    @if ($index === 0)
                                        <flux:icon name="trophy" class="text-yellow-500 h-4 w-4" />
                                    @elseif ($index === 1)
                                        <flux:icon name="medal" class="text-gray-400 h-4 w-4" />
                                    @elseif ($index === 2)
                                        <flux:icon name="award" class="text-orange-500 h-4 w-4" />
                                    @endif
                                </span>

                            </li>
                        @endforeach

                    </ul>

                @endif

            </div>

            <div class="absolute bottom-4 right-4">
                <flux:button size="xs" variant="ghost" href="{{ route('steps.index') }}">
                    See all...
                </flux:button>
            </div>

        </div>

        {{-- FULL MODE --}}
    @else
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">

            <h2 class="text-lg font-semibold mb-4">
                <flux:icon.footprints class="w-5 h-5 inline" />
                Steps Leaderboard
            </h2>

            <div class="flex flex-col md:flex-row gap-4 mb-6 items-end">

                <div>
                    <label class="block text-xs font-medium mb-1">From</label>
                    <input type="date" wire:model.change="startDate" class="border rounded p-2 w-full text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1">To</label>
                    <input type="date" wire:model.change="endDate" class="border rounded p-2 w-full text-sm">
                </div>

                <div class="flex gap-2">

                    @if ($startDate !== $appliedStartDate || $endDate !== $appliedEndDate)
                        <flux:button variant="primary" color="teal" wire:click="filter">
                            Apply
                        </flux:button>
                    @else
                        <flux:button variant="primary" color="teal" wire:click="resetMonthToDate">
                            Month to Date
                        </flux:button>
                    @endif

                </div>

            </div>

            @if ($leaders->isEmpty())

                <p class="text-gray-600 dark:text-gray-300 text-sm">
                    No steps found for selected period.
                </p>
            @else
                <div class="overflow-x-auto">

                    <table class="w-full border-collapse text-sm">

                        <thead>
                            <tr class="border-b text-xs uppercase tracking-wide text-gray-500">
                                <th class="text-left p-2">Rank</th>
                                <th class="text-left p-2">Name</th>
                                <th class="text-left p-2">Total Steps</th>
                                <th class="text-left p-2">Days</th>
                            </tr>
                        </thead>

                        <tbody>

                            @php
                                $topSteps = $leaders->pluck('total_steps')->unique()->take(3)->values();

                                $rangeDays =
                                    \Carbon\Carbon::parse($appliedStartDate)->diffInDays(
                                        \Carbon\Carbon::parse($appliedEndDate),
                                    ) + 1;

                                $consistencyThreshold = max($rangeDays - 1, 1);
                            @endphp

                            @foreach ($leaders as $index => $entry)
                                <tr class="border-b hover:bg-gray-100 dark:hover:bg-gray-700">

                                    <td class="p-2 font-medium w-14">
                                        {{ $index + 1 }}
                                    </td>

                                    <td class="p-2">

                                        <div class="flex items-center gap-2">

                                            <span>{{ $entry->user->name }}</span>

                                            @if ($entry->days_logged >= $consistencyThreshold)
                                                <span class="text-xs" title="Consistent logging">🔥</span>
                                            @endif

                                            @if ($entry->total_steps == ($topSteps[0] ?? null))
                                                <flux:icon name="trophy" class="text-yellow-500 h-4 w-4" />
                                            @elseif ($entry->total_steps == ($topSteps[1] ?? null))
                                                <flux:icon name="medal" class="text-gray-400 h-4 w-4" />
                                            @elseif ($entry->total_steps == ($topSteps[2] ?? null))
                                                <flux:icon name="award" class="text-orange-500 h-4 w-4" />
                                            @endif

                                        </div>

                                    </td>

                                    <td class="p-2 font-semibold text-blue-600 dark:text-blue-400">
                                        {{ number_format($entry->total_steps) }}
                                    </td>

                                    <td class="p-2 text-gray-600 dark:text-gray-300">
                                        {{ $entry->days_logged }}
                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    @endif

</div>
