<x-layouts.app title="Manage Requests">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-xl md:text-2xl font-bold mb-6">All Requests</h1>
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="overflow-x-auto">
                <table class="w-full min-w-max border-collapse border border-gray-200 dark:border-gray-700 text-sm">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-200">
                            <th class="border px-4 py-2 text-left">Employee</th>
                            <th class="border px-4 py-2 text-left">Type</th>
                            <th class="border px-4 py-2 text-left">Dates</th>
                            <th class="border px-4 py-2 text-left">Number of Days</th>
                            <th class="border px-4 py-2 text-left">Status</th>
                            <th class="border px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $r)
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="border px-4 py-2">{{ $r->user->name }}</td>
                                <td class="border px-4 py-2">{{ $r->type }}</td>
                                <td class="border px-4 py-2">{{ $r->start_date }} to {{ $r->end_date }}
                                    ({{ $r->number_of_days }}d)</td>
                                <td class="border px-4 py-2">{{ $r->number_of_days }}</td>
                                <td class="border px-4 py-2">
                                    @php
                                        $badgeColor = match ($r->status) {
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                            'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                            'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                                            'cancelled' => 'bg-gray-100 text-gray-700 dark:bg-gray-700/20 dark:text-gray-300',
                                        };
                                    @endphp
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $badgeColor }}">
                                        {{ ucfirst($r->status) }}
                                    </span>
                                </td>
                                <td class="border px-4 py-2">
                                    <a href="{{ route('requests.show', $r) }}"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-500 dark:text-gray-400 py-4 italic">
                                    No requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>
