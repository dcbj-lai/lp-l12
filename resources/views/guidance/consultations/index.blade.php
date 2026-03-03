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
        <div 
            x-data="{
                showArchiveModal: false,
                archiveId: null
            }"
            class="overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div>

                <table
                    class="w-full border-collapse border border-neutral-200 dark:border-neutral-700 text-sm">

                    <!-- Head -->
                    <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            <th class="border px-3 py-2 text-left">Student</th>
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
                                    {{ $consultation->current_teacher ?? '—' }}
                                </td>

                                <td class="border px-3 py-2 text-xs whitespace-nowrap">
                                    {{ optional($consultation->time_in)?->format('M d, Y h:i A') ?? '—' }}
                                </td>

                                <td class="border px-3 py-2 text-xs whitespace-nowrap">
                                    {{ $consultation->time_out
                                        ? \Carbon\Carbon::parse($consultation->time_out)->format('M d, Y h:i A')
                                        : '—'
                                    }}
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

                                <!-- ✅ Single Action Column -->
                                <td class="border px-3 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-2">

                                        <!-- View -->
                                        <a href="{{ route('guidance.consultations.show', [
                                                'consultation' => $consultation->id,
                                                'return' => 'consultations'
                                            ]) }}"
                                        class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm transition">
                                            View
                                        </a>

                                        <!-- Archive -->
                                        <button
                                            type="button"
                                            @click="
                                                archiveId = {{ $consultation->id }};
                                                showArchiveModal = true;
                                            "
                                            class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm transition">
                                            Archive
                                        </button>

                                    </div>
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

                <!-- Archive Confirmation Modal -->
                <div
                    x-show="showArchiveModal"
                    x-transition
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    style="display: none;"
                >

                    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-xl w-full max-w-md p-6">

                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-3">
                            Archive Consultation
                        </h2>

                        <p class="text-sm text-neutral-600 dark:text-neutral-300 mb-6">
                            Are you sure you want to archive this consultation?
                        </p>

                        <div class="flex justify-end gap-3">

                            <button
                                type="button"
                                @click="showArchiveModal = false"
                                class="px-4 py-2 text-sm rounded-md border border-neutral-300 dark:border-neutral-600 text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                                Cancel
                            </button>

                            <form method="POST"
                                :action="`/guidance/consultations/${archiveId}/archive`">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                    class="px-4 py-2 text-sm rounded-md bg-red-600 hover:bg-red-700 text-white transition">
                                    Yes, Archive
                                </button>
                            </form>

                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>
</x-layouts.app>