<x-layouts.app>
    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Edit Patient Profile</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $patient->first_name }} {{ $patient->last_name }}
                    @if($patient->email) — {{ $patient->email }} @endif
                </p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('clinic.patients.show', $patient) }}"
                   class="inline-flex items-center rounded border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50
                          dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                    Back
                </a>
            </div>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="mb-4 rounded border border-green-200 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation errors --}}
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

        {{-- Form card --}}
        <div class="overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 border border-neutral-200 dark:border-neutral-700"
             x-data="{ type: '{{ old('type', $patient->type) }}' }">

            <div class="flex items-start justify-between gap-4 mb-6">
                <h2 class="text-lg font-semibold">Edit Information</h2>

                <a href="{{ route('clinic.patients.show', $patient) }}"
                   class="text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white underline">
                    Cancel
                </a>
            </div>

            <form method="POST" action="{{ route('clinic.patients.update', $patient) }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Basic --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">First Name *</label>
                        <input name="first_name" value="{{ old('first_name', $patient->first_name) }}" required
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Last Name *</label>
                        <input name="last_name" value="{{ old('last_name', $patient->last_name) }}" required
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Email</label>
                        <input name="email" type="email" value="{{ old('email', $patient->email) }}"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Patient Type *</label>
                        <select name="type" x-model="type" required
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>

                    {{-- Student --}}
                    <div class="w-full" x-show="type === 'student'">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Course</label>
                        <input name="course" value="{{ old('course', $patient->course) }}"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full" x-show="type === 'student'">
                        <input type="hidden" name="is_under_accessibility" value="0">
                        <label class="flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-200">
                            <input type="checkbox" name="is_under_accessibility" value="1"
                                @checked((bool) old('is_under_accessibility', $patient->is_under_accessibility))
                                class="rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500">
                            Under Accessibility
                        </label>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Identifies a student supported by Student Accessibility Services.
                        </p>
                    </div>

                    {{-- Staff --}}
                    <div class="w-full" x-show="type === 'staff'">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Department</label>
                        <input name="department" value="{{ old('department', $patient->department) }}"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full" x-show="type === 'staff'">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Position</label>
                        <input name="position" value="{{ old('position', $patient->position) }}"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Medical + Emergency --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Blood Type</label>
                        <input name="blood_type" value="{{ old('blood_type', $patient->blood_type) }}" placeholder="e.g., O+"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Emergency Contact Person</label>
                        <input name="emergency_contact_person" value="{{ old('emergency_contact_person', $patient->emergency_contact_person) }}"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Emergency Contact Number</label>
                        <input name="emergency_contact_number" value="{{ old('emergency_contact_number', $patient->emergency_contact_number) }}"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600
                                    focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white
                                   hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        Save Changes
                    </button>

                    <a href="{{ route('clinic.patients.show', $patient) }}"
                       class="inline-flex items-center rounded border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50
                              dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
