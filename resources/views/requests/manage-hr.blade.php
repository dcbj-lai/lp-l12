<x-layouts.app title="All Requests (HR)">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl md:text-2xl font-bold">All Requests</h1>

            <form method="POST" action="{{ route('requests.purgeCancelled') }}">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded text-sm font-semibold"
                    onclick="return confirm('Are you sure you want to purge all cancelled requests? This action cannot be undone.')">
                    🗑️ Purge Cancelled Requests
                </button>
            </form>
        </div>

        <!-- 🔍 Filters -->
        <form method="GET" class="grid md:grid-cols-5 gap-3 mb-4 text-sm">
            <!-- Employee Name -->
            <input type="text" name="employee" value="{{ request('employee') }}" placeholder="Employee name"
                class="border rounded p-2 dark:bg-neutral-800 dark:border-neutral-700">

            <!-- Department Dropdown -->
            <select name="department" class="border rounded p-2 dark:bg-neutral-800 dark:border-neutral-700">
                <option value="">All Departments</option>
                @foreach (\App\Models\Department::orderBy('name')->get() as $dept)
                    <option value="{{ $dept->id }}" @selected(request('department') == $dept->id)>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>

            <!-- Type -->
            <select name="type" class="border rounded p-2 dark:bg-neutral-800 dark:border-neutral-700">
                <option value="">All Types</option>
                <option value="PTO" @selected(request('type') === 'PTO')>Leave</option>
                <option value="WFH" @selected(request('type') === 'WFH')>Work From Home</option>
                <option value="LWOP" @selected(request('type') === 'LWOP')>Leave Without Pay</option>
            </select>

            <!-- Status -->
            <select name="status" class="border rounded p-2 dark:bg-neutral-800 dark:border-neutral-700">
                <option value="">All Status</option>
                @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>

            <!-- Clustered Date Range with Labels -->
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

            <!-- Filter & Reset Buttons -->
            <div class="md:col-span-5 flex gap-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-semibold">
                    Filter
                </button>
                <a href="{{ route('requests.manage-hr') }}"
                    class="bg-neutral-500 hover:bg-neutral-600 text-white px-4 py-2 rounded text-sm font-semibold">
                    Reset
                </a>
            </div>
        </form>





        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6 bg-white dark:bg-neutral-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-neutral-200 dark:border-neutral-700">
                    <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            @php
                                function sort_link($label, $column, $sort, $direction)
                                {
                                    $newDir = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
                                    $arrow = $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '';
                                    return '<a href="?sort=' .
                                        $column .
                                        '&direction=' .
                                        $newDir .
                                        '" class="hover:underline">' .
                                        $label .
                                        ' ' .
                                        $arrow .
                                        '</a>';
                                }
                            @endphp

                            <th class="border px-4 py-2">{!! sort_link('Employee', 'employee', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">
                                {!! sort_link('Department', 'department', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">{!! sort_link('Approver', 'approver', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">{!! sort_link('Type', 'type', $sort, $direction) !!}</th>
                            <th class="border px-4 py-2">{!! sort_link('Dates', 'start_date', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">{!! sort_link('Days', 'number_of_days', $sort, $direction) !!}
                            </th>
                            <th class="border px-4 py-2">Reason</th>
                            <th class="border px-4 py-2">Balance</th>
                            <th class="border px-4 py-2">{!! sort_link('Status', 'status', $sort, $direction) !!}</th>
                            <th class="border px-4 py-2">Actions</th>

                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $r)
                            <tr>
                                <td class="border px-4 py-2">{{ $r->user->name }}</td>
                                <td class="border px-4 py-2">{{ optional($r->user->department)->name ?? '—' }}</td>
                                <td class="border px-4 py-2">{{ optional($r->approver)->name ?? '—' }}</td>
                                <td class="border px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <span>
                                            @if ($r->type === 'PTO')
                                                Leave
                                            @elseif ($r->type === 'WFH')
                                                Work From Home
                                            @else
                                                {{ ucfirst($r->type) }}
                                            @endif
                                        </span>

                                        @if ($r->is_offset)
                                            <span title="Does not deduct leave credits"
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                       bg-sky-100 text-sky-700
                       dark:bg-sky-800/30 dark:text-sky-300 cursor-help">
                                                Offset
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="border px-4 py-2 text-xs">{{ $r->start_date }} → {{ $r->end_date }}</td>
                                <td class="border px-4 py-2 text-center">{{ $r->number_of_days }}</td>
                                <td class="border px-4 py-2">{{ $r->reason }}</td>
                                <td class="border px-4 py-2">
                                    @if ($r->type === 'PTO')
                                        {{ optional($r->user->requestCredit)->pto ?? 'N/A' }}
                                    @elseif ($r->type === 'WFH')
                                        {{ optional($r->user->requestCredit)->wfh ?? 'N/A' }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="border px-4 py-2">
                                    @php
                                        $badgeColor = match ($r->status) {
                                            'pending'
                                                => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                            'approved'
                                                => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                            'rejected'
                                                => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                                            'cancelled'
                                                => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-700/20 dark:text-neutral-300',
                                        };
                                    @endphp
                                    <span
                                        class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $badgeColor }}">
                                        {{ ucfirst($r->status) }}
                                    </span>
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <a href="{{ route('requests.show-hr', $r) }}"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10"
                                    class="border px-4 py-3 text-center text-neutral-500 dark:text-neutral-400">
                                    No requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>
