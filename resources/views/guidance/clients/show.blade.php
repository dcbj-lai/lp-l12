<x-layouts.app title="Client Profile">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

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
        <div class="flex flex-wrap items-center justify-between mb-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold">
                    {{ $client->first_name }} {{ $client->last_name }}
                </h1>
                <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                    Student client profile
                </p>
            </div>

            <a href="{{ route('guidance.clients.index') }}"
               class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded-md shadow-sm transition-all duration-150">
                Back to Clients
            </a>
        </div>
        <hr class="mt-4 border-white/40">

        <!-- Client Information Card -->
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6 bg-neutral-800 text-neutral-100">

            <h2 class="text-lg font-semibold mb-6">
                Client Information
            </h2>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-400">
                        Email
                    </dt>
                    <dd class="mt-1 text-sm">
                        {{ $client->email }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-400">
                        First Name
                    </dt>
                    <dd class="mt-1 text-sm">
                        {{ $client->first_name }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-400">
                        Last Name
                    </dt>
                    <dd class="mt-1 text-sm">
                        {{ $client->last_name }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-400">
                        Course
                    </dt>
                    <dd class="mt-1 text-sm">
                        {{ $client->course ?? '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-400">
                        Section
                    </dt>
                    <dd class="mt-1 text-sm">
                        {{ $client->section ?? '—' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-400">
                        Created
                    </dt>
                    <dd class="mt-1 text-sm">
                        {{ optional($client->created_at)->format('M d, Y h:i A') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-neutral-400">
                        Last Updated
                    </dt>
                    <dd class="mt-1 text-sm">
                        {{ optional($client->updated_at)->format('M d, Y h:i A') }}
                    </dd>
                </div>

            </dl>
        </div>

        <!-- Recent Consultations -->
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Recent Consultations</h2>

                <a href="{{ route('guidance.consultations.create', $client) }}"
                   class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md shadow-sm transition-all duration-150">
                    Start a Consultation
                </a>
            </div>

            @if ($consultations->count() === 0)
                <div class="text-neutral-500 dark:text-neutral-400">
                    No consultations recorded yet.
                </div>
            @else

                <div class="overflow-x-auto">
                    <table
                        class="w-full min-w-max border-collapse border border-neutral-200 dark:border-neutral-700 text-sm">

                        <thead>
                            <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                                <th class="border px-4 py-2 text-left">Time In</th>
                                <th class="border px-4 py-2 text-left">Time Out</th>
                                <th class="border px-4 py-2 text-left">Teacher</th>
                                <th class="border px-4 py-2 text-left">After Class</th>
                                <th class="border px-4 py-2 text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody class="text-neutral-800 dark:text-neutral-300">
                            @foreach ($consultations as $log)
                                <tr class="border-b hover:bg-neutral-50 dark:hover:bg-neutral-700 transition">
                                    <td class="border px-4 py-2 whitespace-nowrap">
                                        {{ optional($log->time_in)->format('M d, Y h:i A') ?? '—' }}
                                    </td>

                                    <td class="border px-4 py-2 whitespace-nowrap">
                                        {{ optional($log->time_out)->format('M d, Y h:i A') ?? '—' }}
                                    </td>

                                    <td class="border px-4 py-2 whitespace-nowrap">
                                        {{ $log->current_teacher ?? '—' }}
                                    </td>

                                    <td class="border px-4 py-2 whitespace-nowrap">
                                        @if ($log->after_consultation === 'resume')
                                            Resume class
                                        @elseif ($log->after_consultation === 'go_home')
                                            Go home
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="border px-4 py-2 whitespace-nowrap text-center">
                                        <a href="{{ route('guidance.consultations.show', [
                                                'consultation' => $log->id,
                                                'return_url'   => url()->full(),
                                            ]) }}"
                                           class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded-md shadow-sm transition-all duration-150">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $consultations->withQueryString()->links() }}
                </div>

            @endif
        </div>

    </div>
</x-layouts.app>