<x-layouts.app>
    <div class="max-w-3xl mx-auto mt-10 bg-white dark:bg-zinc-800 shadow rounded-lg p-6 space-y-6">
        <h2 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100 border-b pb-3">
            Edit Attendance Record
        </h2>

        <form action="{{ route('attendance.update', $attendance->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- Employee Name --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Employee</label>
                <input type="text" value="{{ $attendance->user->name }}" disabled
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
            </div>

            {{-- Date --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Date</label>
                <input type="date" name="date" value="{{ $attendance->date->format('Y-m-d') }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
            </div>

            {{-- Check In --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Check In</label>
                <input type="time" name="check_in"
                    value="{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
            </div>

            {{-- Check Out --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Check Out</label>
                <input type="time" name="check_out"
                    value="{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
            </div>



            {{-- Hours Worked (computed) --}}
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
                <input type="number" name="hours_worked" step="0.01" value="{{ $hoursWorked }}" readonly
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white opacity-75 cursor-not-allowed">
            </div>


            {{-- Status (readonly from DB) --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                <input type="text" name="status" value="{{ $attendance->status }}" readonly
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white opacity-75 cursor-not-allowed">
            </div>


            {{-- Remarks --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Remarks</label>
                <input type="text" name="remarks" value="{{ $attendance->remarks }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white cursor-not-allowed"
                    readonly>
            </div>


            {{-- Buttons --}}
            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                @if(request('return') === 'profile')
                <a href="{{ route('guidance.clients.show', $consultation->client_id) }}"
                class="px-4 py-2 border border-neutral-600 text-white rounded">
                            Cancel
                        </a>
                    @else
                        <a href="{{ route('guidance.consultations.index') }}"
                        class="px-4 py-2 border border-neutral-600 text-white rounded">
                            Cancel
                        </a>
                    @endif
                <button type="submit" class="px-3 py-2 text-sm rounded-md bg-blue-600 hover:bg-blue-700 text-white">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
