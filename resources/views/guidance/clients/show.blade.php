<x-layouts.app>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">

        <!-- Breadcrumb -->
        <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
            Health & Wellness
            <span class="mx-1">›</span>
            Guidance
            <span class="mx-1">›</span>
            <a href="{{ route('guidance.clients.index') }}" class="hover:underline">
                Clients
            </a>
            <span class="mx-1">›</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">
                View Details
            </span>
        </div>

        <!-- Header -->
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $client->first_name }} {{ $client->last_name }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Student client profile
                </p>
            </div>

            <a href="{{ route('guidance.clients.index') }}"
               class="inline-flex items-center rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50
                      dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                Back to Clients
            </a>
        </div>

        <!-- Details Card -->
        <div class="rounded-lg shadow bg-white dark:bg-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Client Information</h2>
            </div>

            <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

                    <!-- Identity -->
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $client->email }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">First Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $client->first_name }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Last Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $client->last_name }}</dd>
                    </div>

                    <!-- Academic -->
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Course</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $client->course ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Section</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $client->section ?? '—' }}</dd>
                    </div>

                    <!-- Timestamps -->
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ optional($client->created_at)->format('M d, Y h:i A') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ optional($client->updated_at)->format('M d, Y h:i A') }}
                        </dd>
                    </div>

                </dl>
            </div>
        </div>

<!-- Recent Consultations Card -->
<div class="mt-6 rounded-lg shadow bg-white dark:bg-gray-800 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Consultations</h2>

        <a href="{{ route('guidance.consultations.create', $client) }}"
           class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700
                  dark:bg-indigo-500 dark:hover:bg-indigo-600">
            Start a Consultation
        </a>
    </div>

    <div class="p-6">
        @if (($consultations ?? collect())->count() === 0)
            <div class="text-sm text-gray-600 dark:text-gray-400">
                No consultations recorded yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="text-left px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                                Time In
                            </th>
                            <th class="text-left px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                                Time Out
                            </th>
                            <th class="text-left px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                                Teacher
                            </th>
                            <th class="text-left px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                                After Class
                            </th>
                            <th class="text-center px-4 py-3 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="text-gray-800 dark:text-gray-200">
                        @foreach ($consultations as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                                    {{ optional($log->time_in)->format('M d, Y h:i A') ?? '—' }}
                                </td>

                                <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                                    {{ optional($log->time_out)->format('M d, Y h:i A') ?? '—' }}
                                </td>

                                <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                                    {{ $log->current_teacher ?? '—' }}
                                </td>

                                <td class="px-4 py-2 border border-gray-200 dark:border-gray-600">
                                    @if ($log->after_consultation === 'resume')
                                        Resume class
                                    @elseif ($log->after_consultation === 'go_home')
                                        Go home
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-center">
                                    <a href="{{ route('guidance.consultations.show', [
                                            'consultation' => $log->id,
                                            'return_url'   => url()->full(),   // ✅ goes back to this profile page exactly
                                        ]) }}"
                                    class="px-3 py-1 rounded-md text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

    </div>
</x-layouts.app>