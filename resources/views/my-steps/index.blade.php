<x-layouts.app title="My Daily Steps">
    <div class="max-w-4xl mx-auto py-10 px-6 space-y-6 text-gray-800 dark:text-gray-200">

        {{-- Page Title --}}
        <h1 class="text-lg font-bold text-center mb-6 text-gray-700 dark:text-gray-300">
            <flux:icon name="target" class="w-8 h-8 inline-block stroke-amber-600" />
            My Daily Steps Log
        </h1>

        {{-- Steps Log Form --}}
        <form action="{{ route('my-steps.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="flex flex-col md:flex-row items-center justify-center gap-2">

                <flux:input type="date" name="date" value="{{ old('date') ?? now()->toDateString() }}"
                    max="{{ now()->toDateString() }}" required />

                <flux:input type="number" name="steps" value="{{ old('steps') ?? '' }}" placeholder="Enter steps"
                    min="1" required />

                <flux:button type="submit" variant="primary">
                    Log Steps
                </flux:button>

            </div>
        </form>

        {{-- Previous Steps Logs Table --}}
        <div class="overflow-x-auto shadow-md rounded-lg">
            <table class="w-full border-collapse border border-gray-300 dark:border-gray-700">
                <thead class="bg-gray-600 dark:bg-gray-700 text-white">
                    <tr>
                        <th class="p-3 text-left">
                            <flux:icon name="calendar-days" class="w-5 h-5 inline-block" />
                            Date
                        </th>
                        <th class="p-3 text-left">
                            <flux:icon name="footprints" class="w-5 h-5 inline-block" />
                            Steps
                        </th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse(($stepsLogs ?? collect()) as $log)
                        <tr
                            class="border-t border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-900 transition">

                            {{-- Date --}}
                            <td class="p-3">
                                {{ !empty(optional($log)->date) ? \Carbon\Carbon::parse($log->date)->format('M d, Y') : 'N/A' }}
                            </td>

                            {{-- Steps --}}
                            <td class="p-3 font-semibold text-gray-700 dark:text-gray-300">
                                {{ isset($log->steps) ? $log->steps : '0' }}
                            </td>

                            {{-- Actions --}}
                            <td class="p-3 flex gap-2">

                                {{-- Edit --}}
                                <flux:button href="{{ route('my-steps.edit', $log) }}" variant="outline"
                                    class="border-gray-500 text-gray-500 hover:bg-gray-500 hover:text-white">
                                    <flux:icon name="pencil" class="w-4 h-4 inline-block mr-1" />
                                    Edit
                                </flux:button>

                                {{-- Delete --}}
                                <form action="{{ route('my-steps.destroy', $log) }}" method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <flux:button type="submit" variant="outline"
                                        class="border-red-500 text-red-500 hover:bg-red-500 hover:text-white">
                                        <flux:icon name="trash" class="w-4 h-4 inline-block mr-1" />
                                        Delete
                                    </flux:button>
                                </form>

                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-3 text-center text-gray-500 dark:text-gray-400">
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

        {{-- Fun Motivational Quote --}}
        <div class="text-center mt-6">
            <p class="text-lg italic text-gray-500 dark:text-gray-300 text-xs">
                "I press on toward the goal to win the prize for which God has called me heavenward in Christ Jesus"
                - Phil 3:14
            </p>
        </div>

        {{-- Back to Dashboard --}}
        <div class="text-center mt-6">
            <flux:button href="{{ route('dashboard') }}" variant="outline"
                class="border-gray-500 text-gray-500 hover:bg-gray-500 hover:text-white">
                <flux:icon name="layout-dashboard" class="w-5 h-5 inline-block mr-1" />
                Back to Dashboard
            </flux:button>
        </div>

    </div>
</x-layouts.app>
