<x-layouts.app title="My Visitors">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-xl md:text-2xl font-bold mb-6">My Visitor Logs</h1>

        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6 bg-white dark:bg-neutral-900">
            <div class="mb-4">
                <form method="GET" action="{{ route('visitors.mine') }}" class="flex flex-col sm:flex-row gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search your visitors..."
                        class="flex-1 border rounded px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-800 
                               text-neutral-800 dark:text-neutral-100 border-neutral-300 dark:border-neutral-700 
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-3 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium">
                            Search
                        </button>

                        <button type="button"
                            onclick="window.location='{{ route('visitors.mine') }}'"
                            class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400 dark:bg-neutral-700 dark:hover:bg-neutral-600 text-xs font-medium">
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-max border-collapse border border-neutral-200 dark:border-neutral-700 text-sm">
                    <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            <th class="border px-4 py-2 text-left">Full Name</th>
                            <th class="border px-4 py-2 text-left hidden md:table-cell">Email</th>
                            <th class="border px-4 py-2 text-left">Mobile</th>
                            <th class="border px-4 py-2 text-left hidden md:table-cell">Purpose</th>
                            <th class="border px-4 py-2 text-left">Status</th>
                            <th class="border px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visitors as $visitor)
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                <td class="border px-4 py-2">{{ $visitor->full_name }}</td>
                                <td class="border px-4 py-2 hidden md:table-cell">{{ $visitor->email }}</td>
                                <td class="border px-4 py-2">{{ $visitor->mobile }}</td>
                                <td class="border px-4 py-2 hidden md:table-cell">{{ $visitor->purpose ?? '-' }}</td>
                                <td class="border px-4 py-2">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-300',
                                            'endorsed' => 'bg-blue-100 text-blue-800 dark:bg-blue-800/20 dark:text-blue-300',
                                            'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800/20 dark:text-emerald-300',
                                            'declined' => 'bg-rose-100 text-rose-800 dark:bg-rose-800/20 dark:text-rose-300',
                                            'checked_out' => 'bg-purple-100 text-purple-800 dark:bg-purple-800/20 dark:text-purple-300',
                                        ];
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-medium {{ $statusColors[$visitor->status] ?? 'bg-gray-200 text-gray-800' }}">
                                        {{ ucfirst($visitor->status) }}
                                    </span>
                                </td>
                                <td class="border px-4 py-2">
                                    <a href="{{ route('visitors.show', $visitor->id) }}"
                                        class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-neutral-500 dark:text-neutral-400 py-4 italic">
                                    You have no visitor records yet.
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
