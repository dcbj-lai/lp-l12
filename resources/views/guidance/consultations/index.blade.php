<x-layouts.app title="Consultations">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between mb-4">
            <h1 class="text-xl md:text-2xl font-bold">
                Consultations
            </h1>
        </div>

        <!-- Filters -->
        <form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">

            <div>
                <label class="text-sm font-medium">Search (Name or Email)</label>
                <input
                    type="text"
                    name="q"
                    value="{{ $q ?? request('q') }}"
                    placeholder="e.g., Juan or juan@email.com"
                    class="border px-2 py-1 rounded-md
                           dark:bg-zinc-700 dark:text-white
                           bg-white text-zinc-900"
                />
            </div>

            <div>
                <label class="text-sm font-medium">Date From</label>
                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom ?? request('date_from') }}"
                    class="border px-2 py-1 rounded-md
                           dark:bg-zinc-700 dark:text-white
                           bg-white text-zinc-900"
                />
            </div>

            <div>
                <label class="text-sm font-medium">Date To</label>
                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo ?? request('date_to') }}"
                    class="border px-2 py-1 rounded-md
                           dark:bg-zinc-700 dark:text-white
                           bg-white text-zinc-900"
                />
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm">
                    Filter
                </button>

                <a href="{{ route('guidance.consultations.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm">
                    Reset
                </a>
            </div>
        </form>

        <!-- Table Card -->
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div>

                <table
                    class="w-full border-collapse border border-neutral-200 dark:border-neutral-700 text-sm">

                    <!-- Head -->
                    <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            <th class="border px-3 py-2 text-left">Student</th>
                            <th class="border px-3 py-2 text-left">Email</th>
                            <th class="border px-3 py-2 text-left">Current Teacher</th>
                            <th class="border px-3 py-2 text-left">Time In</th>
                            <th class="border px-3 py-2 text-left">Time Out</th>
                            <th class="border px-3 py-2 text-left">After Consultation</th>
                            <th class="border px-3 py-2 text-left">Remarks</th>
                            <th class="border px-3 py-2 text-left">Action</th>
                        </tr>
                    </thead>

                    <!-- Body -->
                    <tbody class="text-neutral-800 dark:text-neutral-300">
                        @forelse($consultations as $consultation)
                            <tr class="border-b hover:bg-neutral-50 dark:hover:bg-neutral-700 transition">

                                <td class="border px-3 py-2 whitespace-normal break-words">
                                    {{ optional($consultation->client)->first_name }}
                                    {{ optional($consultation->client)->last_name }}
                                </td>

                                <td class="border px-3 py-2 whitespace-normal break-words">
                                    {{ optional($consultation->client)->email ?? '—' }}
                                </td>

                                <td class="border px-3 py-2 whitespace-normal break-words">
                                    {{ $consultation->current_teacher ?? '—' }}
                                </td>

                                <td class="border px-3 py-2 text-xs whitespace-nowrap">
                                    {{ optional($consultation->time_in)->format('M d, Y h:i A') ?? '—' }}
                                </td>

                                <td class="border px-3 py-2 text-xs whitespace-nowrap">
                                    @if($consultation->time_out)
                                        {{ \Carbon\Carbon::parse($consultation->time_out)->format('M d, Y h:i A') }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="border px-3 py-2 whitespace-normal break-words">
                                    {{ $consultation->after_consultation
                                        ? \Illuminate\Support\Str::of($consultation->after_consultation)->replace('_', ' ')->title()
                                        : '—'
                                    }}
                                </td>

                                <td class="border px-3 py-2 whitespace-normal break-words">
                                    {{ \Illuminate\Support\Str::limit($consultation->remarks ?? '', 60) ?: '—' }}
                                </td>

                                <td class="border px-3 py-2 whitespace-nowrap">
                                    <a href="{{ route('guidance.consultations.show', [
                                            'consultation' => $consultation->id,
                                            'return' => 'consultations'
                                        ]) }}"
                                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded-md shadow-sm transition-all duration-150">
                                        View
                                    </a>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    class="px-4 py-6 text-center text-neutral-500 dark:text-neutral-400">
                                    No consultations found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $consultations->withQueryString()->links() }}
                </div>

            </div>
        </div>

    </div>
</x-layouts.app>