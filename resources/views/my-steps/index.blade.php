<x-layouts.app title="My Daily Steps">

    <div class="max-w-4xl mx-auto py-6 px-4 space-y-6 text-gray-800 dark:text-gray-200">

        {{-- Page Title --}}
        <h1 class="text-lg font-bold text-center text-gray-700 dark:text-gray-300">
            <flux:icon name="target" class="w-7 h-7 inline stroke-amber-600 mr-1" />
            My Daily Steps Log
        </h1>

        {{-- Monthly Total Card --}}
        <div
            class="bg-white dark:bg-zinc-900 shadow rounded-xl p-4 text-center border border-gray-200 dark:border-zinc-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                📊 Monthly Total
            </p>

            <p class="text-2xl font-bold text-amber-600">
                {{ number_format($monthlyTotal ?? 0) }}
            </p>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                steps
            </p>
        </div>

        {{-- Steps Log Form --}}
        <form action="{{ route('my-steps.store') }}" method="POST" class="space-y-3">
            @csrf

            <flux:input type="date" name="date" value="{{ old('date') ?? now()->toDateString() }}"
                max="{{ now()->toDateString() }}" required class="w-full" />

            <flux:input type="number" name="steps" value="{{ old('steps') ?? '' }}" placeholder="Enter steps"
                min="1" required class="w-full" />

            <flux:button type="submit" variant="primary" class="w-full md:w-auto">

                <flux:icon name="plus" class="w-4 h-4 mr-1 inline" />
                Log Steps

            </flux:button>

        </form>

        {{-- Steps Logs Table --}}
        <div
            class="overflow-x-auto shadow rounded-lg
        @if (isset($stepsLogs) && $stepsLogs->count() > 5) max-h-[320px] overflow-y-auto @endif">

            <table class="w-full border-collapse border border-gray-300 dark:border-gray-700 text-sm">

                <thead class="bg-gray-600 dark:bg-gray-700 text-white sticky top-0 z-10">
                    <tr>
                        <th class="p-3 text-left">
                            <flux:icon name="calendar-days" class="w-4 h-4 inline mr-1" />
                            Date
                        </th>

                        <th class="p-3 text-left">
                            <flux:icon name="footprints" class="w-4 h-4 inline mr-1" />
                            Steps
                        </th>

                        <th class="p-3 text-left">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody>

                    @forelse(($stepsLogs ?? collect()) as $log)
                        <tr
                            class="border-t border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-900">

                            {{-- Date --}}
                            <td class="p-3">
                                {{ \Carbon\Carbon::parse($log->date)->format('M d, Y') }}
                            </td>

                            {{-- Steps --}}
                            <td class="p-3 font-semibold">
                                {{ number_format($log->steps) }}
                            </td>

                            {{-- Actions --}}
                            <td class="p-3">

                                <div class="flex flex-col gap-2 md:flex-row">

                                    <flux:button href="{{ route('my-steps.edit', $log) }}" variant="outline"
                                        class="w-full md:w-auto text-sm">

                                        <flux:icon name="pencil" class="w-4 h-4 mr-1 inline" />
                                        Edit

                                    </flux:button>

                                    <form action="{{ route('my-steps.destroy', $log) }}" method="POST">
                                        @csrf
                                        @method('DELETE')

                                        <flux:button type="submit" variant="outline" class="w-full md:w-auto text-sm">

                                            <flux:icon name="trash" class="w-4 h-4 mr-1 inline" />
                                            Delete

                                        </flux:button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="3" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                No steps logged yet — time to move! 🚶‍♂️
                            </td>
                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Pagination --}}
        @if (isset($stepsLogs) && method_exists($stepsLogs, 'links'))
            <div class="mt-4">
                {{ $stepsLogs->links() }}
            </div>
        @endif

        {{-- Motivational Quote --}}
        <div class="text-center text-xs text-gray-500 dark:text-gray-400 mt-6 italic">
            "I press on toward the goal to win the prize for which God has called me heavenward in Christ Jesus"
            <br>
            – Philippians 3:14 ✨
        </div>

        {{-- Bottom Actions --}}
        <div class="flex flex-col gap-3 pt-4 md:flex-row">

            <flux:button href="{{ route('steps.index') }}" variant="outline" class="w-full md:w-auto">

                <flux:icon name="circle-star" class="w-5 h-5 mr-1" />
                See Leaderboard

            </flux:button>

        </div>

    </div>

</x-layouts.app>
