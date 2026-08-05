@php
    $isStaff = $patientType === 'staff';
    $tab = $isStaff ? 'staff' : 'students';
    $typeLabel = $isStaff ? 'Staff' : 'Student';
@endphp

<x-layouts.app :title="'Create '.$typeLabel.' Patient'">
    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                    Create {{ $typeLabel }} Patient
                </h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $typeLabel }} records default to patient type {{ $typeLabel }}.
                </p>
            </div>

            <a href="{{ route('clinic.patients.index', ['tab' => $tab]) }}"
                class="inline-flex items-center justify-center rounded border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">
                Back to {{ $isStaff ? 'Staff' : 'Students' }}
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

        <div class="overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 border border-neutral-200 dark:border-neutral-700">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">{{ $typeLabel }} Information</h2>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        {{ $isStaff ? 'Department and position apply to staff records.' : 'Staff-only department and position fields do not apply.' }}
                    </p>
                </div>
                <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:border-blue-500/40 dark:bg-blue-950/40 dark:text-blue-300">
                    {{ $typeLabel }}
                </span>
            </div>

            <form method="POST" action="{{ route('clinic.patients.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="type" value="{{ $patientType }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">First Name *</label>
                        <input name="first_name" value="{{ old('first_name') }}" required
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Last Name *</label>
                        <input name="last_name" value="{{ old('last_name') }}" required
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Email</label>
                        <input name="email" type="email" value="{{ old('email') }}"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    @if ($isStaff)
                        <div class="w-full">
                            <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Department</label>
                            <select name="department"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department }}" @selected(old('department') === $department)>
                                        {{ $department }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full">
                            <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Position</label>
                            <input name="position" value="{{ old('position') }}"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    @else
                        <div class="w-full">
                            <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Course</label>
                            <input name="course" value="{{ old('course') }}"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="w-full">
                            <input type="hidden" name="is_under_accessibility" value="0">
                            <label class="flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                <input type="checkbox" name="is_under_accessibility" value="1"
                                    @checked((bool) old('is_under_accessibility'))
                                    class="rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500">
                                Under Accessibility
                            </label>
                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                Identifies a student supported by Student Accessibility Services.
                            </p>
                        </div>
                    @endif

                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Blood Type</label>
                        <input name="blood_type" value="{{ old('blood_type') }}" placeholder="e.g., O+"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Emergency Contact Person</label>
                        <input name="emergency_contact_person" value="{{ old('emergency_contact_person') }}"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-200">Emergency Contact Number</label>
                        <input name="emergency_contact_number" value="{{ old('emergency_contact_number') }}"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 dark:bg-neutral-900 dark:text-neutral-100 dark:border-neutral-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex flex-col gap-2 pt-2 sm:flex-row">
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 sm:w-auto">
                        Create
                    </button>

                    <a href="{{ route('clinic.patients.index', ['tab' => $tab]) }}"
                        class="inline-flex w-full items-center justify-center rounded border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800 sm:w-auto">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
