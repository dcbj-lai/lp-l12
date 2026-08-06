<x-layouts.app title="Consultations">
    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h1 class="text-xl md:text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                Consultations
            </h1>
        </div>

        <!-- Filters -->
        <form method="GET" class="mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 items-end">

                <div class="sm:col-span-2 xl:col-span-2">
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                        Search (Name or Email)
                    </label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? request('q') }}"
                        placeholder="e.g., Juan or juan@email.com"
                        class="w-full border border-neutral-300 dark:border-neutral-600 px-3 py-2 rounded-md
                               bg-white text-zinc-900 dark:bg-zinc-700 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                        Date From
                    </label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ $dateFrom ?? request('date_from') }}"
                        class="w-full border border-neutral-300 dark:border-neutral-600 px-3 py-2 rounded-md
                               bg-white text-zinc-900 dark:bg-zinc-700 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                        Date To
                    </label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ $dateTo ?? request('date_to') }}"
                        class="w-full border border-neutral-300 dark:border-neutral-600 px-3 py-2 rounded-md
                               bg-white text-zinc-900 dark:bg-zinc-700 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <button
                        type="submit"
                        class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                        Filter
                    </button>

                    <a href="{{ route('guidance.consultations.index') }}"
                       class="w-full sm:w-auto text-center bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <!-- Table Card -->
        <div
            x-data="{
                showArchiveModal: false,
                archiveId: null,
                returnPage: 'consultations'
            }"
            class="overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700"
        >
            <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
               <table class="w-full table-fixed border-collapse text-sm">
                <thead>
                    <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                        <th class="w-[12%] border-b border-neutral-200 dark:border-neutral-700 px-3 py-3 text-left">Student</th>
                        <th class="w-[16%] border-b border-neutral-200 dark:border-neutral-700 px-3 py-3 text-left">Check-in Teacher</th>
                        <th class="w-[16%] border-b border-neutral-200 dark:border-neutral-700 px-3 py-3 text-left">Check-out Teacher</th>
                        <th class="w-[14%] border-b border-neutral-200 dark:border-neutral-700 px-3 py-3 text-left">Time In</th>
                        <th class="w-[14%] border-b border-neutral-200 dark:border-neutral-700 px-3 py-3 text-left">Time Out</th>
                        <th class="w-[10%] border-b border-neutral-200 dark:border-neutral-700 px-3 py-3 text-left">After Consultation</th>
                        <th class="w-[18%] border-b border-neutral-200 dark:border-neutral-700 px-3 py-3 text-left">Action</th>
                    </tr>
                </thead>

                <tbody class="text-neutral-800 dark:text-neutral-300 bg-white dark:bg-neutral-900">
                    @forelse($consultations as $consultation)
                        <tr class="border-b border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition align-top">
                            <td class="px-3 py-3 whitespace-normal break-words">
                                {{ optional($consultation->client)->first_name }}
                                {{ optional($consultation->client)->last_name }}
                            </td>

                            <td class="px-3 py-3 whitespace-normal break-words">
                                {{ $consultation->check_in_teacher ?: 'No Teacher Assigned' }}
                            </td>

                            <td class="px-3 py-3 whitespace-normal break-words">
                                {{ $consultation->current_teacher ?: 'No Teacher Assigned' }}
                            </td>

                            <td class="px-3 py-3 text-xs whitespace-nowrap">
                                {{ optional($consultation->time_in)?->format('M d, Y h:i A') ?? '—' }}
                            </td>

                            <td class="px-3 py-3 text-xs whitespace-nowrap">
                                {{ $consultation->time_out
                                    ? \Carbon\Carbon::parse($consultation->time_out)->format('M d, Y h:i A')
                                    : '—'
                                }}
                            </td>

                            <td class="px-3 py-3 whitespace-normal break-words">
                                {{ $consultation->after_consultation
                                    ? \Illuminate\Support\Str::of($consultation->after_consultation)->replace('_', ' ')->title()
                                    : '—'
                                }}
                            </td>

                           <td class="px-3 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('guidance.consultations.show', [
                                        'consultation' => $consultation->id,
                                        'return' => 'consultations'
                                    ]) }}"
                                class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium px-2.5 py-1.5 rounded-md shadow-sm transition">
                                    View
                                </a>

                                <a href="{{ route('guidance.consultations.edit', [
                                        'consultation' => $consultation->id,
                                        'return_url'   => url()->full()
                                    ]) }}"
                                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-2.5 py-1.5 rounded-md shadow-sm transition">
                                    Edit
                                </a>

                                <button
                                    type="button"
                                    @click="
                                        archiveId = {{ $consultation->id }};
                                        returnPage = 'consultations';
                                        showArchiveModal = true;
                                    "
                                    class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-2.5 py-1.5 rounded-md shadow-sm transition">
                                    Archive
                                </button>
                            </div>
                        </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-neutral-500 dark:text-neutral-400">
                                No consultations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $consultations->withQueryString()->links() }}
            </div>

            <!-- Archive Confirmation Modal -->
            <div
                x-show="showArchiveModal"
                x-transition
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
            >
                <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-xl w-full max-w-md p-6 border border-neutral-200 dark:border-neutral-700">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-3">
                        Archive Consultation
                    </h2>

                    <p class="text-sm text-neutral-600 dark:text-neutral-300 mb-6">
                        Are you sure you want to archive this consultation?
                    </p>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3">
                        <button
                            type="button"
                            @click="showArchiveModal = false"
                            class="w-full sm:w-auto px-4 py-2 text-sm rounded-md border border-neutral-300 dark:border-neutral-600 text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                            Cancel
                        </button>

                        <form method="POST" :action="'/guidance/consultations/' + archiveId + '/archive'" class="w-full sm:w-auto">
                            @csrf
                            @method('DELETE')

                            <input type="hidden" name="return" :value="returnPage">

                            <button
                                type="submit"
                                class="w-full sm:w-auto px-4 py-2 text-sm rounded-md bg-red-600 hover:bg-red-700 text-white transition">
                                Yes, Archive
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>