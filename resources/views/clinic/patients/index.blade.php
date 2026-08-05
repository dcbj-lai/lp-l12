<x-layouts.app title="Clinic – Patients">
    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">

        <!-- Header Row -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h1 class="text-xl md:text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                Patients
            </h1>
        </div>

        <div
            x-data="{ activeTab: '{{ request('tab', 'students') }}' }"
            class="space-y-4"
        >

            <!-- Tabs -->
            <div class="border-b border-neutral-200 dark:border-neutral-700">
                <nav class="-mb-px flex gap-6" aria-label="Tabs">
                    <button
                        type="button"
                        @click="activeTab = 'students'"
                        :class="activeTab === 'students'
                            ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200'"
                        class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition"
                    >
                        Students
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'staff'"
                        :class="activeTab === 'staff'
                            ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                            : 'border-transparent text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200'"
                        class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition"
                    >
                        Staff
                    </button>
                </nav>
            </div>

            <!-- Students Tab -->
            <div x-show="activeTab === 'students'" x-cloak class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Student Patients</h2>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Create and manage student clinic records.</p>
                    </div>

                    <a href="{{ route('clinic.patients.create', ['tab' => 'students']) }}"
                        class="inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:w-auto">
                        Create
                    </a>
                </div>

                <!-- Student Filters -->
                <form method="GET" action="{{ route('clinic.patients.index') }}" class="mb-4">
                    <input type="hidden" name="tab" value="students">

                    <div class="grid grid-cols-1 lg:grid-cols-[minmax(280px,420px)_auto] gap-3 items-end">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                Search Student
                            </label>
                            <input
                                type="text"
                                name="student_q"
                                value="{{ request('student_q') }}"
                                placeholder="Search by name or email"
                                class="w-full border border-neutral-300 dark:border-neutral-600 px-3 py-2 rounded-md
                                    bg-white text-zinc-900 dark:bg-zinc-700 dark:text-white
                                    focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2">
                            <button
                                type="submit"
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition"
                            >
                                Filter
                            </button>

                            <a
                                href="{{ route('clinic.patients.index', ['tab' => 'students']) }}"
                                class="w-full sm:w-auto text-center bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition"
                            >
                                Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Student Table -->
                <div class="overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700">
                    <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                        <table class="min-w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                                    <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Name</th>
                                    <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Email</th>
                                    <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Course</th>
                                    <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-center whitespace-nowrap">Action</th>
                                </tr>
                            </thead>

                            <tbody class="text-neutral-800 dark:text-neutral-300 bg-white dark:bg-neutral-900">
                                @forelse ($students as $student)
                                    <tr class="border-b border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition">
                                        <td class="px-4 py-3 whitespace-normal break-words min-w-[180px]">
                                            {{ $student->first_name }} {{ $student->last_name }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-normal break-all min-w-[220px]">
                                            {{ $student->email ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap min-w-[120px]">
                                            {{ $student->course ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-center min-w-[150px]">
                                            <a href="{{ route('clinic.patients.show', $student) }}"
                                            class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-2 rounded-md shadow-sm transition-all duration-150 w-full sm:w-auto">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center px-4 py-6 text-neutral-500 dark:text-neutral-400">
                                            No students found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $students->appends(array_merge(request()->query(), ['tab' => 'students']))->links() }}
                    </div>
                </div>
            </div>

            <!-- Staff Tab -->
            <div x-show="activeTab === 'staff'" x-cloak class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Staff Patients</h2>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Create and manage staff clinic records.</p>
                    </div>

                    <a href="{{ route('clinic.patients.create', ['tab' => 'staff']) }}"
                        class="inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:w-auto">
                        Create
                    </a>
                </div>

                <!-- Staff Filters -->
                <form method="GET" action="{{ route('clinic.patients.index') }}" class="mb-4">
                    <input type="hidden" name="tab" value="staff">

                    <div class="grid grid-cols-1 lg:grid-cols-[minmax(280px,420px)_auto] gap-3 items-end">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                Search Staff
                            </label>
                            <input
                                type="text"
                                name="staff_q"
                                value="{{ request('staff_q') }}"
                                placeholder="Search by name or email"
                                class="w-full border border-neutral-300 dark:border-neutral-600 px-3 py-2 rounded-md
                                    bg-white text-zinc-900 dark:bg-zinc-700 dark:text-white
                                    focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2">
                            <button
                                type="submit"
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition"
                            >
                                Filter
                            </button>

                            <a
                                href="{{ route('clinic.patients.index', ['tab' => 'staff']) }}"
                                class="w-full sm:w-auto text-center bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition"
                            >
                                Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Staff Table -->
                <div class="overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700">
                    <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                        <table class="min-w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200">
                                    <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Name</th>
                                    <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Email</th>
                                    <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Department</th>
                                    <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-left whitespace-nowrap">Position</th>
                                    <th class="border-b border-neutral-200 dark:border-neutral-700 px-4 py-3 text-center whitespace-nowrap">Action</th>
                                </tr>
                            </thead>

                            <tbody class="text-neutral-800 dark:text-neutral-300 bg-white dark:bg-neutral-900">
                                @forelse ($staff as $employee)
                                    <tr class="border-b border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition">
                                        <td class="px-4 py-3 whitespace-normal break-words min-w-[180px]">
                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-normal break-all min-w-[220px]">
                                            {{ $employee->email ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap min-w-[140px]">
                                            {{ $employee->department ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap min-w-[140px]">
                                            {{ $employee->position ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-center min-w-[150px]">
                                            <a href="{{ route('clinic.patients.show', $employee) }}"
                                            class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-2 rounded-md shadow-sm transition-all duration-150 w-full sm:w-auto">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center px-4 py-6 text-neutral-500 dark:text-neutral-400">
                                            No staff found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $staff->appends(array_merge(request()->query(), ['tab' => 'staff']))->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>
