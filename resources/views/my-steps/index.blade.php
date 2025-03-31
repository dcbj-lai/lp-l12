<x-layouts.app title="My Daily Steps">
    <div class="max-w-4xl mx-auto py-10 px-6 space-y-6 text-gray-800 dark:text-gray-200">

        {{-- Page Title --}}
        <h1 class="text-4xl font-bold text-center mb-6 text-gray-700 dark:text-gray-300">
            <flux:icon name="target" class="w-10 h-10 inline-block stroke-amber-600" /> My Daily Steps Log
        </h1>

        {{-- Steps Log Form --}}
        <form action="{{ route('my-steps.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="flex flex-col md:flex-row items-center justify-center gap-2">
                <flux:input type="number" name="steps" placeholder="Enter today's steps"
                    class="w-full md:w-3/4 border-2 border-gray-300 focus:border-gray-500 bg-gray-50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-200 p-2 rounded-lg"
                    required />
                <flux:button type="submit" variant="primary"
                    class="w-full md:w-auto bg-gray-600 hover:bg-gray-700 text-white font-semibold">
                    <flux:icon name="file-clock" class="w-5 h-5 inline-block mr-1" /> Log Steps
                </flux:button>
            </div>
        </form>

        {{-- Previous Steps Logs Table --}}
        <div class="overflow-x-auto shadow-md rounded-lg">
            <table class="w-full border-collapse border border-gray-300 dark:border-gray-700">
                <thead class="bg-gray-600 dark:bg-gray-700 text-white">
                    <tr>
                        <th class="p-3 text-left">
                            <flux:icon name="calendar-days" class="w-5 h-5 inline-block" /> Date
                        </th>
                        <th class="p-3 text-left">
                            <flux:icon name="footprints" class="w-5 h-5 inline-block" /> Steps
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stepsLogs ?? [] as $log)
                        <tr
                            class="border-t border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-900 transition">
                            <td class="p-3">{{ $log->date ? date('M d, Y', strtotime($log->date)) : 'N/A' }}</td>
                            <td class="p-3 font-semibold text-gray-700 dark:text-gray-300">
                                {{ $log->steps ?? '0' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="p-3 text-center text-gray-500 dark:text-gray-400">
                                No steps logged yet — time to move! 🚶‍♂️
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $stepsLogs->links() }}
        </div>

        {{-- Fun Motivational Quote --}}
        <div class="text-center mt-6">
            <p class="text-lg italic text-gray-500 dark:text-gray-300">
                "Every step you take brings you closer to a healthier you!" 🥇
            </p>
        </div>

        {{-- Go to Dashboard Button --}}
        <div class="text-center mt-6">
            <flux:button href="{{ route('dashboard') }}" variant="outline"
                class="border-gray-500 text-gray-500 hover:bg-gray-500 hover:text-white">
                <flux:icon name="layout-dashboard" class="w-5 h-5 inline-block mr-1" /> Back to Dashboard
            </flux:button>
        </div>
    </div>
</x-layouts.app>
