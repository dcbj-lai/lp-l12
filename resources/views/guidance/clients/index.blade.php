<x-layouts.app title="Guidance - Student Clients">
    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-neutral-900 dark:text-neutral-100">Guidance - Student Clients</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Manage student Guidance records.</p>
            </div>
            <a href="{{ route('guidance.clients.create') }}"
               class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Create
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-md border border-green-300 bg-green-50 p-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" action="{{ route('guidance.clients.index') }}" class="mb-4">
            <div class="grid grid-cols-1 items-end gap-3 lg:grid-cols-[minmax(280px,420px)_auto]">
                <div>
                    <label for="q" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-300">Search Student</label>
                    <input id="q" type="text" name="q" value="{{ $q }}" placeholder="Search by name or email"
                           class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-zinc-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-zinc-700 dark:text-white">
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 sm:w-auto">Filter</button>
                    <a href="{{ route('guidance.clients.index') }}" class="w-full rounded-md bg-gray-500 px-4 py-2 text-center text-sm font-medium text-white hover:bg-gray-600 sm:w-auto">Reset</a>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white p-4 shadow-xl dark:border-neutral-700 dark:bg-neutral-900 sm:p-6">
            <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                <table class="min-w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-200">
                            <th class="border-b border-neutral-200 px-4 py-3 text-left dark:border-neutral-700">Name</th>
                            <th class="border-b border-neutral-200 px-4 py-3 text-left dark:border-neutral-700">Email</th>
                            <th class="border-b border-neutral-200 px-4 py-3 text-left dark:border-neutral-700">Course</th>
                            <th class="border-b border-neutral-200 px-4 py-3 text-left dark:border-neutral-700">Section</th>
                            <th class="border-b border-neutral-200 px-4 py-3 text-center dark:border-neutral-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white text-neutral-800 dark:bg-neutral-900 dark:text-neutral-300">
                        @forelse ($clients as $client)
                            <tr class="border-b border-neutral-200 transition hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">
                                <td class="min-w-[180px] px-4 py-3">{{ $client->full_name }}</td>
                                <td class="min-w-[220px] break-all px-4 py-3">{{ $client->email }}</td>
                                <td class="min-w-[120px] px-4 py-3">{{ $client->course ?: '—' }}</td>
                                <td class="min-w-[120px] px-4 py-3">{{ $client->section ?: '—' }}</td>
                                <td class="min-w-[150px] px-4 py-3 text-center">
                                    <a href="{{ route('guidance.clients.show', $client) }}"
                                       class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 font-semibold text-white hover:bg-blue-700">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-neutral-500 dark:text-neutral-400">No students found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $clients->links() }}</div>
        </div>
    </div>
</x-layouts.app>