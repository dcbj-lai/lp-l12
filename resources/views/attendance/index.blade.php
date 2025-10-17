<x-layouts.app title="Attendance Records">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between mb-4">
            <h1 class="text-xl md:text-2xl font-bold">All Attendance Records</h1>

            <a href="{{ route('attendance.create') }}"
                class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md shadow-sm transition-all duration-150">
                + Create Manual Entry
            </a>
        </div>

        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-max border-collapse border border-neutral-200 dark:border-neutral-700 text-sm">
                    <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            <th class="border px-4 py-2 text-left">Employee</th>
                            <th class="border px-4 py-2 text-left">Date</th>
                            <th class="border px-4 py-2 text-left">Check-In</th>
                            <th class="border px-4 py-2 text-left">Check-Out</th>
                            <th class="border px-4 py-2 text-left">Status</th>
                            <th class="border px-4 py-2 text-left">Remarks</th>
                            <th class="border px-4 py-2 text-left">Hours Worked</th>
                            <th class="border px-4 py-2 text-left">Created At</th>
                            <th class="border px-4 py-2 text-left">Updated At</th>
                            <th class="border px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-neutral-800 dark:text-neutral-300">
                        @forelse ($attendances as $a)
                            @php
                                $officialTimeIn = new DateTime(config('app.official_time_in'));
                                $checkInTime = $a->check_in ? new DateTime($a->check_in) : null;
                                $remarkCheckin = $a->check_in
                                    ? ($checkInTime->format('H:i:s') <= $officialTimeIn->format('H:i:s')
                                        ? 'text-green-500 dark:text-green-400 font-semibold'
                                        : 'text-red-500 dark:text-red-400 font-semibold')
                                    : 'text-black dark:text-white font-semibold';
                            @endphp

                            <tr class="border-b">
                                <td class="border px-4 py-2 whitespace-nowrap">{{ $a->user->name ?? '—' }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap">{{ $a->date }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap">
                                    <span class="{{ $remarkCheckin }}">
                                        {{ $a->check_in ? \Carbon\Carbon::parse($a->check_in)->timezone('Asia/Manila')->format('h:i A') : '—' }}
                                    </span>
                                </td>
                                <td class="border px-4 py-2 whitespace-nowrap">
                                    {{ $a->check_out ? \Carbon\Carbon::parse($a->check_out)->timezone('Asia/Manila')->format('h:i A') : '—' }}
                                </td>
                                <td class="border px-4 py-2 whitespace-nowrap">{{ $a->status ?? '—' }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap">{{ $a->remarks ?? '—' }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap">
                                    {{ $a->hours_worked ? number_format($a->hours_worked, 2) . ' hrs' : '—' }}
                                </td>
                                <td class="border px-4 py-2 whitespace-nowrap">
                                    {{ $a->created_at ? $a->created_at->timezone('Asia/Manila')->format('Y-m-d h:i A') : '—' }}
                                </td>
                                <td class="border px-4 py-2 whitespace-nowrap">
                                    {{ $a->updated_at ? $a->updated_at->timezone('Asia/Manila')->format('Y-m-d h:i A') : '—' }}
                                </td>
                                <td class="border px-4 py-2 whitespace-nowrap text-center">
                                    <a href="{{ route('attendance.edit', $a->id) }}"
                                        class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded-md shadow-sm transition-all duration-150">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center px-4 py-6 text-neutral-500 dark:text-neutral-400">
                                    No attendance records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $attendances->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
