<x-layouts.app>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">

        <!-- Breadcrumb -->
        <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
            Health & Wellness
            <span class="mx-1">›</span>
            Guidance
            <span class="mx-1">›</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">
                Clients
            </span>
        </div>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Guidance – Student Clients
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Official list of students under the Guidance Office.
            </p>
        </div>

        <div class="mb-4">
            <form id="clientsSearchForm" method="GET" action="{{ route('guidance.clients.index') }}" class="w-full md:w-1/3">
                <input
                    id="clientsSearchInput"
                    type="text"
                    name="q"
                    value="{{ $q ?? '' }}"
                    placeholder="Search students..."
                    autocomplete="off"
                    class="w-full px-4 py-2 rounded-lg border
                        bg-white text-gray-900 border-gray-300
                        dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600
                        focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-lg shadow bg-white dark:bg-gray-800">
            <table class="min-w-full border-collapse">

                <!-- Head -->
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                            Name
                        </th>
                        <th class="text-left px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                            Email
                        </th>
                        <th class="text-center px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                            Action
                        </th>
                    </tr>
                </thead>

                <!-- Body -->
                <tbody class="text-gray-800 dark:text-gray-200">
                    @forelse ($clients as $client)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">


                            <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                                {{ $client->first_name }} {{ $client->last_name }}
                            </td>

                            <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                                {{ $client->email }}
                            </td>

                            <td class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-center">
                                <a
                                    href="{{ route('guidance.clients.show', $client) }}"
                                    class="px-3 py-1 rounded-md text-sm font-medium
                                           bg-indigo-600 text-white
                                           hover:bg-indigo-700
                                           dark:bg-indigo-500 dark:hover:bg-indigo-600
                                           transition"
                                >
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                No students found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $clients->links() }}
        </div>

    </div>
</x-layouts.app>

<script>
(function () {
  const form = document.getElementById('clientsSearchForm');
  const input = document.getElementById('clientsSearchInput');
  if (!form || !input) return;

  let t = null;

  input.addEventListener('input', function () {
    clearTimeout(t);
    t = setTimeout(() => form.submit(), 400); // debounce delay
  });
})();
</script>