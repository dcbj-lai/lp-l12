<div class="p-4">
    <!-- Date Range Pickers -->
    <div class="mb-4 flex gap-2 items-center">
        <input type="date" wire:model="dateFrom"
            class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200" />

        <input type="date" wire:model="dateTo"
            class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200" />

        <!-- Filter Button -->
        <flux:button wire:click="loadLeaderboard" variant="filled" color="blue">
            Filter
        </flux:button>

        <!-- Clear Button -->
        <flux:button wire:click="clearFilters" variant="outline" color="gray">
            Clear
        </flux:button>
    </div>

    <!-- Error Message -->
    @if (session()->has('error'))
        <div class="mb-4 p-2 bg-red-500 text-white rounded">{{ session('error') }}</div>
    @endif

    <!-- Leaderboard Table -->
    <div class="overflow-x-auto">
        <table class="table-auto w-full border-collapse border border-gray-200 dark:border-gray-600">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th
                        class="p-2 border dark:border-gray-600 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">
                        #</th>
                    <th
                        class="p-2 border dark:border-gray-600 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">
                        User</th>
                    <th
                        class="p-2 border dark:border-gray-600 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Total Steps</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaderboard as $index => $entry)
                    <tr class="odd:bg-gray-50 even:bg-white dark:odd:bg-gray-800 dark:even:bg-gray-700">
                        <td class="p-2 border dark:border-gray-600 text-gray-800 dark:text-gray-200">
                            <!-- Show trophy/medal/award icons for top 3 -->
                            @if($index === 0)
                                <flux:icon name="trophy" class="text-yellow-500 h-5 w-5" />
                            @elseif($index === 1)
                                <flux:icon name="medal" class="text-gray-400 h-5 w-5" />
                            @elseif($index === 2)
                                <flux:icon name="award" class="text-orange-500 h-5 w-5" />
                            @else
                                {{ $index + 1 }}
                            @endif
                        </td>
                        <td class="p-2 border dark:border-gray-600 text-gray-800 dark:text-gray-200">
                            {{ $entry->user->name }}
                        </td>
                        <td class="p-2 border dark:border-gray-600 text-gray-800 dark:text-gray-200">
                            {{ number_format($entry->total_steps) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center p-4 text-gray-500 dark:text-gray-400">No steps logged for this
                            period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
