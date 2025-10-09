<x-layouts.app title="Visitor Verification">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-xl md:text-2xl font-bold mb-6">Visitor Logs</h1>

        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6 bg-white dark:bg-neutral-900">
            <div class="mb-4">
                <form method="GET" action="{{ route('frontdesk.visitors') }}" class="flex flex-col sm:flex-row gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search visitors..."
                        class="flex-1 border rounded px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-800 
                               text-neutral-800 dark:text-neutral-100 border-neutral-300 dark:border-neutral-700 
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-3 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium">
                            Search
                        </button>

                        <button type="button" onclick="window.location='{{ route('frontdesk.visitors') }}'"
                            class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400 dark:bg-neutral-700 dark:hover:bg-neutral-600 text-xs font-medium">
                            Clear
                        </button>
                        <a href="{{ route('frontdesk.visitors.csv') }}" class="inline-flex items-center justify-center px-3 py-1 rounded bg-green-600 
           hover:bg-green-700 text-white text-xs font-medium">
                            Download CSV
                        </a>

                    </div>
                </form>
            </div>

            <!-- Responsive table wrapper -->
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-max border-collapse border border-neutral-200 dark:border-neutral-700 text-sm">
                    <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            <th class="border px-4 py-2 text-left">Full Name</th>
                            <th class="border px-4 py-2 text-left">Company</th>
                            <th class="border px-4 py-2 text-left">Set Date</th>
                            <th class="border px-4 py-2 text-left hidden md:table-cell">Email</th>
                            <th class="border px-4 py-2 text-left">Mobile</th>
                            <th class="border px-4 py-2 text-left hidden lg:table-cell">Person Visited</th>
                            <th class="border px-4 py-2 text-left hidden md:table-cell">Purpose</th>
                            <th class="border px-4 py-2 text-left hidden md:table-cell">Check-in Time</th>
                            <th class="border px-4 py-2 text-left hidden md:table-cell">Check-out Time</th>
                            <th class="border px-4 py-2 text-left">Status</th>
                            <th class="border px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visitors as $visitor)
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                @php
                                    if (!function_exists('batchColor')) {
                                        function batchColor($id)
                                        {
                                            if (!$id)
                                                return null;
                                            $hue = hexdec(substr(md5($id), 0, 4)) % 360;
                                            return "hsl($hue, 45%, 80%)";
                                        }
                                    }
                                @endphp
                                <td class="border px-4 py-2 flex items-center gap-2">
                                    @if ($visitor->batch_id)
                                        <span
                                            class="inline-flex items-center justify-center text-[10px] font-semibold rounded-full px-2 py-0.5 border border-neutral-300/50 dark:border-neutral-700/50"
                                            title="Batch: {{ $visitor->batch_id }}"
                                            style="background-color: {{ batchColor($visitor->batch_id) }};">
                                            {{ Str::upper(Str::substr($visitor->batch_id, 0, 3)) }}
                                        </span>
                                    @endif
                                    <span>{{ $visitor->full_name ?? '-' }}</span>
                                </td>
                                <td class="border px-4 py-2">{{ $visitor->company ?? '-' }}</td>
                                <td class="border px-4 py-2">
                                    @if($visitor->visit_date)
                                        {{ \Carbon\Carbon::parse($visitor->visit_date)->format('M d, Y') }}<br>
                                        <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ \Carbon\Carbon::parse($visitor->visit_date)->format('h:i A') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="border px-4 py-2 hidden md:table-cell">{{ $visitor->email ?? '-' }}</td>
                                <td class="border px-4 py-2">{{ $visitor->mobile ?? '-' }}</td>
                                <td class="border px-4 py-2 hidden lg:table-cell">
                                    {{ optional($visitor->visitedUser)->name ?? '-' }}
                                </td>
                                <td class="border px-4 py-2 hidden md:table-cell">{{ $visitor->purpose ?? '-' }}</td>
                                <td class="border px-4 py-2 hidden md:table-cell">
                                    {{ $visitor->check_in_at ? $visitor->check_in_at->format('M d, Y h:i A') : '-' }}
                                </td>
                                <td class="border px-4 py-2 hidden md:table-cell">
                                    {{ $visitor->check_out_at ? $visitor->check_out_at->format('M d, Y h:i A') : '-' }}
                                </td>
                                <td class="border px-4 py-2">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                            'endorsed' => 'bg-blue-100 text-blue-800 dark:bg-blue-800/20 dark:text-blue-300',
                                            'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                            'checked_in' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-800/20 dark:text-cyan-300',
                                            'declined' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                                            'checked_out' => 'bg-purple-100 text-purple-800 dark:bg-purple-800/20 dark:text-purple-300',
                                        ];
                                    @endphp

                                    <span
                                        class="inline-block px-3 py-1 rounded-full text-xs font-medium {{ $statusColors[$visitor->status] ?? 'bg-gray-200 text-gray-800' }}">
                                        {{ $visitor->status ? ucfirst($visitor->status) : '-' }}
                                    </span>
                                </td>
                                <td class="border px-4 py-2">
                                    <a href="{{ route('frontdesk.visitors.show', $visitor->id) }}"
                                        class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-neutral-500 dark:text-neutral-400 py-4 italic">
                                    No visitor logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>


            </div>

            <div class="mt-6">
                {{ $visitors->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>
