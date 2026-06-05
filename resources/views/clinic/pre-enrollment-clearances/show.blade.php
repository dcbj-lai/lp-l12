<x-layouts.app title="Pre-enrollment Medical Clearance">
    @php
        $findingsHeading = match ($clearance->clearance_status) {
            \App\Models\PreEnrollmentMedicalClearance::STATUS_CLEARED_WITH_CONDITIONS => 'Conditions / Restrictions',
            \App\Models\PreEnrollmentMedicalClearance::STATUS_NOT_CLEARED => 'Reason for Not Cleared',
            default => 'Findings',
        };
        $recommendationsHeading = $clearance->clearance_status === \App\Models\PreEnrollmentMedicalClearance::STATUS_PENDING
            ? 'Additional Requirements'
            : 'Recommendations / Follow-up Actions';
    @endphp
    <div class="max-w-5xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Pre-enrollment Medical Clearance</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">{{ $clearance->applicant_name }}</p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('clinic.pre-enrollment-clearances.pdf', $clearance) }}"
                    class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Download PDF
                </a>
                <a href="{{ route('clinic.pre-enrollment-clearances.index') }}"
                    class="inline-flex items-center justify-center rounded border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                    Back
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-xl dark:border-neutral-700 dark:bg-neutral-800">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase text-neutral-500 dark:text-neutral-400">Applicant</dt>
                    <dd class="mt-1 font-medium text-neutral-900 dark:text-neutral-100">{{ $clearance->applicant_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-neutral-500 dark:text-neutral-400">Status</dt>
                    <dd class="mt-1 font-medium text-neutral-900 dark:text-neutral-100">{{ $clearance->statusLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-neutral-500 dark:text-neutral-400">Email</dt>
                    <dd class="mt-1 text-neutral-900 dark:text-neutral-100">{{ $clearance->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-neutral-500 dark:text-neutral-400">Contact Number</dt>
                    <dd class="mt-1 text-neutral-900 dark:text-neutral-100">{{ $clearance->contact_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-neutral-500 dark:text-neutral-400">Intended Course</dt>
                    <dd class="mt-1 text-neutral-900 dark:text-neutral-100">{{ $clearance->intended_course ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-neutral-500 dark:text-neutral-400">Assessment Date</dt>
                    <dd class="mt-1 text-neutral-900 dark:text-neutral-100">{{ $clearance->assessment_date?->format('M d, Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-neutral-500 dark:text-neutral-400">Issued By</dt>
                    <dd class="mt-1 text-neutral-900 dark:text-neutral-100">{{ $clearance->signatoryName() }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-neutral-500 dark:text-neutral-400">Issued At</dt>
                    <dd class="mt-1 text-neutral-900 dark:text-neutral-100">{{ $clearance->issued_at?->format('M d, Y g:i A') ?? '-' }}</dd>
                </div>
            </div>

            <div class="mt-6 grid gap-5">
                <div>
                    <h2 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $findingsHeading }}</h2>
                    <p class="mt-2 whitespace-pre-line rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-sm text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">{{ $clearance->findings ?: '-' }}</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $recommendationsHeading }}</h2>
                    <p class="mt-2 whitespace-pre-line rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-sm text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">{{ $clearance->recommendations ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
