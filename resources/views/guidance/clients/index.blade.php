<x-layouts.app title="Guidance – Student Clients">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        <!-- Header Row -->
        <div class="flex flex-wrap items-center justify-between mb-4">
            <h1 class="text-xl md:text-2xl font-bold">
                Guidance – Student Clients
            </h1>
        </div>

        <!-- Filters -->
        <form method="GET"
              action="{{ route('guidance.clients.index') }}"
              class="mb-4 flex flex-wrap gap-2 items-end">

            <div>
                <label class="text-sm font-medium">Search Student</label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search by name or email"
                    class="border px-2 py-1 rounded-md
                           dark:bg-zinc-700 dark:text-white
                           bg-white text-zinc-900"
                >
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm">
                    Filter
                </button>

                <a href="{{ route('guidance.clients.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm">
                    Reset
                </a>
            </div>
        </form>

        <!-- Table Card -->
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="overflow-x-auto">

                <table
                    class="w-full min-w-max border-collapse border border-neutral-200 dark:border-neutral-700 text-sm">

                    <!-- Header -->
                    <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            <th class="border px-4 py-2 text-left">Name</th>
                            <th class="border px-4 py-2 text-left">Email</th>
                            <th class="border px-4 py-2 text-left">Course</th>
                            <th class="border px-4 py-2 text-left">Section</th>
                            <th class="border px-4 py-2 text-center">Action</th>
                        </tr>
                    </thead>

                    <!-- Body -->
                    <tbody class="text-neutral-800 dark:text-neutral-300">
                        @forelse ($clients as $client)
                            <tr class="border-b hover:bg-neutral-50 dark:hover:bg-neutral-700 transition">
                                <td class="border px-4 py-2 whitespace-nowrap">
                                    {{ $client->first_name }} {{ $client->last_name }}
                                </td>

                                <td class="border px-4 py-2 whitespace-nowrap">
                                    {{ $client->email }}
                                </td>

                                <td class="border px-4 py-2 whitespace-nowrap">
                                    {{ $client->course ?? '—' }}
                                </td>

                                <td class="border px-4 py-2 whitespace-nowrap">
                                    {{ $client->section ?? '—' }}
                                </td>

                                <td class="border px-4 py-2 whitespace-nowrap text-center">
                                    <a href="{{ route('guidance.clients.show', $client) }}"
                                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded-md shadow-sm transition-all duration-150">
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

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $clients->withQueryString()->links() }}
                </div>

            </div>
        </div>

    </div>
</x-layouts.app>