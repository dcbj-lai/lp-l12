<x-layouts.app>

    <div class="max-w-3xl mx-auto mt-10 bg-white dark:bg-zinc-800 shadow rounded-lg p-6 space-y-6">

        <h2 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100 border-b pb-3">
            Edit Attendance Record
        </h2>

        <form
            action="{{ route('attendance.update', $attendance->id) }}"
            method="POST"
            class="space-y-4"
        >
            @csrf
            @method('PUT')


            {{-- Employee --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Employee
                </label>

                <input
                    type="text"
                    value="{{ $attendance->user->name }}"
                    readonly
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white"
                >
            </div>


            {{-- Date --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Date
                </label>

                <input
                    type="date"
                    name="date"
                    value="{{ $attendance->date->format('Y-m-d') }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white"
                >
            </div>


            {{-- Check In --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Check In
                </label>

                <input
                    type="time"
                    name="check_in"
                    value="{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white"
                >
            </div>


            {{-- Check Out --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Check Out
                </label>

                <input
                    type="time"
                    name="check_out"
                    value="{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white"
                >
            </div>


            {{-- Hours Worked --}}
            @php
                $hoursWorked = 0;

                if ($attendance->check_in && $attendance->check_out) {
                    $hoursWorked = round(
                        (strtotime($attendance->check_out) - strtotime($attendance->check_in)) / 3600,
                        2
                    );
                }
            @endphp

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Hours Worked
                </label>

                <input
                    type="number"
                    step="0.01"
                    value="{{ $hoursWorked }}"
                    readonly
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white opacity-75"
                >
            </div>


            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Status
                </label>

                <input
                    type="text"
                    value="{{ $attendance->status }}"
                    readonly
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white opacity-75"
                >
            </div>


            {{-- Remarks --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Remarks
                </label>

                <input
                    type="text"
                    value="{{ $attendance->remarks }}"
                    readonly
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white opacity-75"
                >
            </div>


            {{-- Buttons --}}
            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">

                <a
                    href="{{ route('attendance.index') }}"
                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded text-zinc-700 dark:text-white"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</x-layouts.app>