<x-layouts.app title="Guidance – Student Clients">
    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">

        <!-- Header Row -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h1 class="text-xl md:text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                Guidance – Student Clients
            </h1>
        </div>

        <!-- Filters -->
        <form method="GET"
            action="{{ route('guidance.clients.index') }}"
            class="mb-4">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(280px,420px)_auto] gap-3 items-end">

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                        Search Student
                    </label>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search by name or email"
                        class="w-full border border-neutral-300 dark:border-neutral-600 px-3 py-2 rounded-md
                            bg-white text-zinc-900 dark:bg-zinc-700 dark:text-white
                            focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="submit"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                        Filter
                    </button>

                    <a href="{{ route('guidance.clients.index') }}"
                    class="w-full sm:w-auto text-center bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <!-- Table Card -->
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700">
            <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">

                <table class="min-w-full border-collapse text-sm">
                    <!-- Header -->
                    <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Name</th>
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Email</th>
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Course</th>
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Section</th>
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-center whitespace-nowrap">Action</th>
                        </tr>
                    </thead>

                    <!-- Body -->
                    <tbody class="text-neutral-800 dark:text-neutral-300 bg-white dark:bg-neutral-900">
                        @forelse ($clients as $client)
                            <tr class="border-b border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition">
                                <td class="px-4 py-3 whitespace-normal break-words min-w-[180px]">
                                    {{ $client->first_name }} {{ $client->last_name }}
                                </td>

                                <td class="px-4 py-3 whitespace-normal break-all min-w-[220px]">
                                    {{ $client->email }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap min-w-[120px]">
                                    {{ $client->course ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap min-w-[120px]">
                                    {{ $client->section ?? '—' }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap text-center min-w-[150px]">
                                    <a href="{{ route('guidance.clients.show', $client) }}"
                                       class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-2 rounded-md shadow-sm transition-all duration-150 w-full sm:w-auto">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="text-center px-4 py-6 text-neutral-500 dark:text-neutral-400">
                                    No students found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $clients->withQueryString()->links() }}
            </div>
        </div>

    </div>
</x-layouts.app>