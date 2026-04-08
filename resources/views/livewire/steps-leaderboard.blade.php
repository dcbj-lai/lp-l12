<x-dashboard-card variant="danger" class="p-4">

    {{-- ===================== --}}
    {{-- CARD MODE --}}
    {{-- ===================== --}}
    @if ($mode === 'card')

        <div class="relative flex flex-col aspect-square">

            <!-- Title -->
            <div class="absolute top-4 left-4">
                <h2 class="text-sm font-semibold text-gray-400 flex items-center gap-1">
                    <flux:icon.footprints class="w-4 h-4" />
                    Top Steppers ({{ \Carbon\Carbon::parse($appliedStartDate)->format('F') }})
                </h2>
            </div>

            <!-- Content -->
            <div class="flex flex-col justify-center items-center flex-grow px-4 text-center">

                @if ($leaders->isEmpty())
                    <p class="text-gray-500 text-xs">
                        No steps logged this month yet.
                    </p>
                @else
                    <ul class="space-y-3 w-full max-w-xs text-sm">

                        @foreach ($leaders as $index => $entry)
                            <li class="flex justify-between items-center">

                                <span class="text-gray-400 font-medium tracking-wide">
                                    {{ $index + 1 }}. {{ $entry->user->preferred_name ?? $entry->user->name }}
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

            <!-- Footer -->
            <div class="absolute bottom-4 right-4">
                <flux:button size="xs" variant="ghost" href="{{ route('steps.index') }}">
                    See all...
                </flux:button>
            </div>

        </div>

    @endif


    {{-- ===================== --}}
    {{-- FULL MODE --}}
    {{-- ===================== --}}
    @if ($mode === 'full')

        <div class="flex flex-col gap-4">

            <!-- Header + Filters -->
            <div class="flex flex-col gap-3">

                <!-- Title -->
                <div>
                    <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
                        <flux:icon.footprints class="w-5 h-5" />
                        Leaderboard
                    </h2>
                    <p class="text-xs text-gray-500">
                        {{ \Carbon\Carbon::parse($appliedStartDate)->format('M d') }} -
                        {{ \Carbon\Carbon::parse($appliedEndDate)->format('M d, Y') }}
                    </p>
                </div>

                <!-- Controls -->
                <div class="flex flex-col gap-2 md:flex-row md:flex-wrap md:items-center">

                    <!-- MTD -->
                    <flux:button class="w-full md:w-auto" size="sm" variant="ghost" wire:click="resetMonthToDate">
                        Month to Date
                    </flux:button>

                    <!-- Date Inputs -->
                    <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                        <input type="date" wire:model.live="startDate"
                            class="w-full md:w-auto text-sm border rounded-md px-3 py-2 
                                   dark:bg-zinc-700 dark:text-white">

                        <input type="date" wire:model.live="endDate"
                            class="w-full md:w-auto text-sm border rounded-md px-3 py-2 
                                   dark:bg-zinc-700 dark:text-white">
                    </div>

                    <!-- Apply -->
                    <flux:button class="w-full md:w-auto" size="sm" variant="primary" wire:click="filter">
                        Apply
                    </flux:button>

                    <!-- Back -->
                    <flux:button class="w-full md:w-auto" size="sm" variant="ghost" href="{{ route('dashboard') }}"
                        icon="arrow-left">
                        Dashboard
                    </flux:button>

                </div>

            </div>

            <!-- Content -->
            @if ($leaders->isEmpty())
                <p class="text-sm text-gray-500">
                    No steps logged for this period.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">

                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-zinc-700">
                                <th class="py-2 pr-4 w-10">#</th>
                                <th class="py-2 pr-4">Name</th>
                                <th class="py-2 text-right">Steps</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($leaders as $index => $entry)
                                <tr class="border-b border-gray-100 dark:border-zinc-800 last:border-none">

                                    <td class="py-2 pr-4 text-gray-400">
                                        {{ $index + 1 }}
                                    </td>

                                    <td class="py-2 pr-4 text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                        {{ $entry->user->preferred_name ?? $entry->user->name }}

                                        @if ($index === 0)
                                            <flux:icon name="trophy" class="text-yellow-500 h-4 w-4" />
                                        @elseif ($index === 1)
                                            <flux:icon name="medal" class="text-gray-400 h-4 w-4" />
                                        @elseif ($index === 2)
                                            <flux:icon name="award" class="text-orange-500 h-4 w-4" />
                                        @endif
                                    </td>

                                    <td class="py-2 text-right font-semibold text-blue-600 dark:text-blue-400">
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

</x-dashboard-card>
