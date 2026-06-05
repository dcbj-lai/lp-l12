<x-layouts.app title="Issue Pre-enrollment Medical Clearance">
    <div class="max-w-5xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Issue Pre-enrollment Medical Clearance</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                    Create a prefilled clearance form for a new student applicant.
                </p>
            </div>

            <a href="{{ route('clinic.pre-enrollment-clearances.index') }}"
                class="inline-flex items-center justify-center rounded border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                Back
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-200 bg-red-50 p-4 text-red-800">
                <p class="font-medium mb-2">Please fix the following:</p>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 shadow-xl dark:border-neutral-700 dark:bg-neutral-800 sm:p-6">
            <form method="POST" action="{{ route('clinic.pre-enrollment-clearances.store') }}" class="space-y-6"
                x-data="{
                    patients: @js($studentPatients),
                    applicantName: @js(old('applicant_name', '')),
                    intendedCourse: @js(old('intended_course', '')),
                    email: @js(old('email', '')),
                    contactNumber: @js(old('contact_number', '')),
                    matchedPatient: null,

                    syncApplicant() {
                        const name = this.applicantName.trim().toLowerCase();

                        this.matchedPatient = this.patients.find((patient) => patient.name.toLowerCase() === name) || null;

                        if (!this.matchedPatient) {
                            return;
                        }

                        if (!this.email && this.matchedPatient.email) {
                            this.email = this.matchedPatient.email;
                        }

                        if (!this.contactNumber && this.matchedPatient.contact_number) {
                            this.contactNumber = this.matchedPatient.contact_number;
                        }

                        if (!this.intendedCourse && this.matchedPatient.course) {
                            this.intendedCourse = this.matchedPatient.course;
                        }
                    },
                }"
                x-init="syncApplicant()">
                @csrf

                <div>
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Applicant Details</h2>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">This does not create a normal clinic visit record.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Applicant Name *</label>
                        <input name="applicant_name" value="{{ old('applicant_name') }}" x-model="applicantName" @input.debounce.150ms="syncApplicant" @change="syncApplicant" list="student-patient-options" autocomplete="off" required
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-100">
                        <datalist id="student-patient-options">
                            @foreach ($studentPatients as $patient)
                                <option value="{{ $patient['name'] }}" label="{{ $patient['details'] ?: 'Existing student patient' }}"></option>
                            @endforeach
                        </datalist>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Choose an existing student patient or type a new applicant name.
                        </p>
                        <p x-show="matchedPatient" x-cloak class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                            Existing student patient matched. Email, contact number, and course fill in when blank.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Intended Course / Program</label>
                        <input name="intended_course" value="{{ old('intended_course') }}" x-model="intendedCourse"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" x-model="email"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Contact Number</label>
                        <input name="contact_number" value="{{ old('contact_number') }}" x-model="contactNumber"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Assessment Date *</label>
                        <input type="date" name="assessment_date" value="{{ old('assessment_date', $defaultAssessmentDate) }}" required
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Clearance Status *</label>
                        <select name="clearance_status" required
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-100">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('clearance_status', $defaultStatus) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Findings</label>
                        <textarea name="findings" rows="4"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-100">{{ old('findings') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Recommendations / Pending Requirements</label>
                        <textarea name="recommendations" rows="4"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-100">{{ old('recommendations') }}</textarea>
                    </div>
                </div>

                <div class="flex flex-col gap-2 pt-2 sm:flex-row">
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 sm:w-auto">
                        Create Clearance
                    </button>

                    <a href="{{ route('clinic.pre-enrollment-clearances.index') }}"
                        class="inline-flex w-full items-center justify-center rounded border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800 sm:w-auto">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
