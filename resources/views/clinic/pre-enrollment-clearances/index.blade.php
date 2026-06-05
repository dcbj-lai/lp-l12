<x-layouts.app title="Pre-enrollment Medical Clearances">
    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                    Pre-enrollment Medical Clearances
                </h1>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Clearance forms for new student applicants.
                </p>
            </div>

            <a href="{{ route('clinic.pre-enrollment-clearances.create') }}"
                class="inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:w-auto">
                Issue Clearance
            </a>
        </div>

        <form method="GET" action="{{ route('clinic.pre-enrollment-clearances.index') }}" class="mb-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(240px,1fr)_220px_auto] sm:items-end">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Search</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, email, or intended course"
                        class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-zinc-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Status</label>
                    <select name="status"
                        class="w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-neutral-600 dark:bg-zinc-700 dark:text-white">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit"
                        class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 sm:w-auto">
                        Filter
                    </button>
                    <a href="{{ route('clinic.pre-enrollment-clearances.index') }}"
                        class="w-full rounded-md bg-gray-500 px-4 py-2 text-center text-sm font-medium text-white transition hover:bg-gray-600 sm:w-auto">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <div class="overflow-hidden border border-neutral-200 bg-white shadow-xl dark:border-neutral-700 dark:bg-neutral-900 sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-[920px] w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-200">
                            <th class="border-b border-neutral-200 px-4 py-3 text-left dark:border-neutral-700">Applicant</th>
                            <th class="border-b border-neutral-200 px-4 py-3 text-left dark:border-neutral-700">Intended Course</th>
                            <th class="border-b border-neutral-200 px-4 py-3 text-left dark:border-neutral-700">Assessment Date</th>
                            <th class="border-b border-neutral-200 px-4 py-3 text-left dark:border-neutral-700">Status</th>
                            <th class="border-b border-neutral-200 px-4 py-3 text-left dark:border-neutral-700">Issued By</th>
                            <th class="border-b border-neutral-200 px-4 py-3 text-center dark:border-neutral-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white text-neutral-800 dark:bg-neutral-900 dark:text-neutral-300">
                        @forelse ($clearances as $clearance)
                            <tr class="border-b border-neutral-200 transition hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $clearance->applicant_name }}</div>
                                    <div class="text-xs text-neutral-500">{{ $clearance->email ?? $clearance->contact_number ?? 'No contact provided' }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $clearance->intended_course ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $clearance->assessment_date?->format('M d, Y') ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @php($statusClass = match ($clearance->clearance_status) {
                                        'cleared' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/40 dark:bg-emerald-950/40 dark:text-emerald-300',
                                        'cleared_with_conditions' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-400/40 dark:bg-sky-950/40 dark:text-sky-300',
                                        'pending_requirements' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-400/40 dark:bg-amber-950/40 dark:text-amber-300',
                                        default => 'border-red-200 bg-red-50 text-red-700 dark:border-red-400/40 dark:bg-red-950/40 dark:text-red-300',
                                    })
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ $clearance->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $clearance->signatoryName() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('clinic.pre-enrollment-clearances.show', $clearance) }}"
                                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-700">
                                            View
                                        </a>
                                        <a href="{{ route('clinic.pre-enrollment-clearances.pdf', $clearance) }}"
                                            class="inline-flex items-center justify-center rounded-md border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-50 dark:border-blue-400/60 dark:text-blue-300 dark:hover:bg-blue-950/40">
                                            PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-neutral-500 dark:text-neutral-400">
                                    No pre-enrollment clearances found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4">
                {{ $clearances->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>
