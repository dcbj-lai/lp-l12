<x-layouts.app title="All Attendance (Admin)">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl md:text-2xl font-bold">All Attendance Records</h1>
        </div>

        <!-- 🔍 Filters -->
        <form method="GET" class="grid md:grid-cols-5 gap-3 mb-4 text-sm">
            <!-- Employee Name -->
            <input type="text" name="employee" value="{{ request('employee') }}" placeholder="Employee name"
                class="border rounded p-2 dark:bg-neutral-800 dark:border-neutral-700">

            <!-- Department Dropdown -->
            <select name="department" class="border rounded p-2 dark:bg-neutral-800 dark:border-neutral-700">
                <option value="">All Departments</option>
                @foreach(\App\Models\Department::orderBy('name')->get() as $dept)
                    <option value="{{ $dept->id }}" @selected(request('department') == $dept->id)>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>

            <!-- Status -->
            <select name="status" class="border rounded p-2 dark:bg-neutral-800 dark:border-neutral-700">
                <option value="">All Status</option>
                @foreach(['On Time', 'Late', 'Absent', 'Present'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ $status }}
                    </option>
                @endforeach
            </select>

            <!-- Date Range -->
            <div class="flex gap-2 md:col-span-5">
                <div class="flex-1">
                    <label for="date_from"
                        class="block text-xs text-neutral-600 dark:text-neutral-400 mb-1">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="border rounded p-2 dark:bg-neutral-800 dark:border-neutral-700 w-full">
                </div>
                <div class="flex-1">
                    <label for="date_to" class="block text-xs text-neutral-600 dark:text-neutral-400 mb-1">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="border rounded p-2 dark:bg-neutral-800 dark:border-neutral-700 w-full">
                </div>
            </div>

            <!-- Buttons -->
            <div class="md:col-span-5 flex gap-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-semibold">
                    Filter
                </button>
                <a href="{{ route('attendance.index') }}"
                    class="bg-neutral-500 hover:bg-neutral-600 text-white px-4 py-2 rounded text-sm font-semibold">
                    Reset
                </a>
            </div>
        </form>

        <!-- 🧾 Attendance Table -->
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6 bg-white dark:bg-neutral-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-neutral-200 dark:border-neutral-700">
                    <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            @php
                                function sort_link($label, $column, $sort, $direction)
                                {
                                    $newDir = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
                                    $arrow = $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '';
                                    return '<a href="?sort=' . $column . '&direction=' . $newDir . '" class="hover:underline">' . $label . ' ' . $arrow . '</a>';
                                }
                            @endphp

                            <th class="border px-4 py-2">{!! sort_link('Employee', 'employee', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">
                                {!! sort_link('Department', 'department', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">{!! sort_link('Date', 'date', $sort, $direction) !!}</th>
                            <th class="border px-4 py-2">{!! sort_link('Check-In', 'check_in', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">{!! sort_link('Check-Out', 'check_out', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">
                                {!! sort_link('Hours Worked', 'hours_worked', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">{!! sort_link('Status', 'status', $sort, $direction) !!}</th>
                            <th class="border px-4 py-2">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attendances as $a)
                            <tr>
                                <td class="border px-4 py-2">{{ $a->user->name }}</td>
                                <td class="border px-4 py-2">{{ optional($a->user->department)->name ?? '—' }}</td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($a->date)->format('Y-m-d') }}</td>
                                <td class="border px-4 py-2">{{ $a->check_in ?? '—' }}</td>
                                <td class="border px-4 py-2">{{ $a->check_out ?? '—' }}</td>
                                <td class="border px-4 py-2 text-center">{{ number_format($a->hours_worked, 2) }}</td>
                                <td class="border px-4 py-2 text-center">
                                    @php
                                        $badgeColor = match ($a->status) {
                                            'On Time' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                            'Late' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                            'Absent' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                                            'Present' => 'bg-blue-100 text-blue-800 dark:bg-blue-800/20 dark:text-blue-300',
                                            default => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-700/20 dark:text-neutral-300'
                                        };
                                    @endphp
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $badgeColor }}">
                                        {{ $a->status }}
                                    </span>
                                </td>
                                <td class="border px-4 py-2">{{ $a->remarks ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-neutral-500 py-4 italic">
                                    No attendance records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $attendances->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>
