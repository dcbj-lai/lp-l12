<x-layouts.app title="Client Profile">

    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <div class="mb-3 text-sm text-neutral-600 dark:text-neutral-400">
            Health & Wellness
            <span class="mx-1">›</span>
            Guidance
            <span class="mx-1">›</span>

            <a href="{{ route('guidance.clients.index') }}" class="hover:underline">
                Clients
            </a>

            <span class="mx-1">›</span>

            <span class="font-semibold text-neutral-900 dark:text-neutral-200">
                View Details
            </span>
        </div>

        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                    {{ $client->first_name }} {{ $client->last_name }}
                </h1>

                <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                    Student client profile
                </p>
            </div>

            <a href="{{ route('guidance.clients.index') }}"
               class="inline-flex items-center justify-center bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded-md shadow-sm transition-all duration-150 w-full sm:w-auto">
                Back to Clients
            </a>
        </div>

        <hr class="mt-4 border-neutral-200 dark:border-neutral-700">

        <!-- Client Information -->
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6 mb-6 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 border border-neutral-200 dark:border-neutral-700">
            <h2 class="text-lg font-semibold mb-6">
                Client Information
            </h2>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Email</dt>
                    <dd class="mt-1 text-sm break-all">{{ $client->email }}</dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">First Name</dt>
                    <dd class="mt-1 text-sm">{{ $client->first_name }}</dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Last Name</dt>
                    <dd class="mt-1 text-sm">{{ $client->last_name }}</dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Course</dt>
                    <dd class="mt-1 text-sm">{{ $client->course ?? '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Section</dt>
                    <dd class="mt-1 text-sm">{{ $client->section ?? '—' }}</dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Created</dt>
                    <dd class="mt-1 text-sm">
                        {{ optional($client->created_at)->format('M d, Y h:i A') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Last Updated</dt>
                    <dd class="mt-1 text-sm">
                        {{ optional($client->updated_at)->format('M d, Y h:i A') }}
                    </dd>
                </div>

            </dl>
        </div>

        <!-- Recent Consultations -->
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                    Recent Consultations
                </h2>

                <a href="{{ route('guidance.consultations.create', $client) }}"
                   class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md shadow-sm transition-all duration-150 w-full sm:w-auto">
                    Start a Consultation
                </a>
            </div>

            @if ($consultations->count() === 0)

                <div class="text-neutral-500 dark:text-neutral-400">
                    No consultations recorded yet.
                </div>

            @else

            <div
                x-data="{
                    showArchiveModal: false,
                    archiveId: null,
                    returnPage: 'client'
                }"
            >
                <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                    <table class="min-w-full border-collapse text-sm">
                        <thead>
                        <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Time In</th>
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Time Out</th>
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Teacher in Check-In</th>
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Teacher in Check-Out</th>
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">After Consultation</th>
                            <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-center whitespace-nowrap">Action</th>
                        </tr>
                    </thead>

                    <tbody class="text-neutral-800 dark:text-neutral-300 bg-white dark:bg-neutral-900">
                        @foreach ($consultations as $log)
                        <tr class="border-b border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition">
                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                {{ optional($log->time_in)?->format('M d, Y h:i A') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                {{ optional($log->time_out)?->format('M d, Y h:i A') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $log->check_in_teacher ?: 'No Teacher Assigned' }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $log->current_teacher ?: 'No Teacher Assigned' }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($log->after_consultation === 'resume')
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                        Resume Class
                                    </span>
                                @elseif ($log->after_consultation === 'go_home')
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                                        Go Home
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                           <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex flex-col sm:flex-row justify-center gap-2">
                                    <a href="{{ route('guidance.consultations.show', [
                                            'consultation' => $log->id,
                                            'return_url' => url()->full()
                                        ]) }}"
                                    class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm transition">
                                        View
                                    </a>

                                    <a href="{{ route('guidance.consultations.edit', [
                                            'consultation' => $log->id,
                                            'return_url' => url()->full()
                                        ]) }}"
                                    class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm transition">
                                        Edit
                                    </a>

                                    <button
                                        type="button"
                                        @click="
                                            archiveId = {{ $log->id }};
                                            returnPage = 'client';
                                            showArchiveModal = true;
                                        "
                                        class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm transition">
                                        Archive
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>

                <!-- Archive Modal -->
                <div
                    x-show="showArchiveModal"
                    x-transition.opacity
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                >
                    <div
                        @click.away="showArchiveModal = false"
                        class="bg-white dark:bg-neutral-800 rounded-xl shadow-2xl w-full max-w-md p-6 border border-neutral-200 dark:border-neutral-700"
                    >
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
                                class="px-4 py-2 text-sm rounded-md border border-neutral-300 dark:border-neutral-600 text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition w-full sm:w-auto">
                                Cancel
                            </button>

                            <form method="POST"
                                  :action="'/guidance/consultations/' + archiveId + '/archive'"
                                  class="w-full sm:w-auto">
                                @csrf
                                @method('DELETE')

                                <input type="hidden" name="return" :value="returnPage">

                                <button
                                    type="submit"
                                    class="px-4 py-2 text-sm rounded-md bg-red-600 hover:bg-red-700 text-white transition w-full sm:w-auto">
                                    Yes, Archive
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $consultations->withQueryString()->links() }}
            </div>

            @endif
        </div>
    </div>

</x-layouts.app>