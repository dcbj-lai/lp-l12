<x-layouts.app title="Edit Attendance">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-xl md:text-2xl font-bold mb-4 dark:text-gray-200">Edit Attendance</h1>
        <form action="{{ route('attendance.update', $attendance->id) }}" method="POST"
            class="bg-white dark:bg-gray-800 shadow-md rounded p-6 space-y-4">
            @csrf
            @method('PUT')

            <!-- Date Field (Read-Only) -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2" for="date">Date</label>
                <input type="text" name="date" value="{{ $attendance->date }}" readonly
                    class="border p-2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded">
            </div>

            <!-- Check-In Time -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2" for="check_in">Check-In</label>
                <input type="datetime-local" name="check_in"
                    value="{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('Y-m-d\TH:i') : '' }}"
                    class="border p-2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded">
            </div>

            <!-- Check-Out Time -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2" for="check_out">Check-Out</label>
                <input type="datetime-local" name="check_out"
                    value="{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('Y-m-d\TH:i') : '' }}"
                    class="border p-2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded">
            </div>
            <!-- Hours Worked -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2" for="date">Hours Worked</label>
                <input type="text" name="date" value="{{ $attendance->hours_worked }}" readonly
                    class="border p-2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded">
            </div>

            <!-- Status Dropdown -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2" for="status">Status</label>
                <select name="status"
                    class="border p-2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded"
                    required>
                    <option value="Absent" {{ $attendance->status == 'Absent' ? 'selected' : '' }}>Absent</option>
                    <option value="Present" {{ $attendance->status == 'Present' ? 'selected' : '' }}>Present</option>
                </select>
            </div>

            <!-- Remarks (Read-Only, Auto-Set to Full Department Name + PNC Staff Name) -->
            <div>
                <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2" for="remarks">Remarks</label>
                <input type="text" name="remarks"
                    value="{{ auth()->user()->department->name . ': ' . auth()->user()->name }}" readonly
                    class="border p-2 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded">
            </div>

            <!-- Buttons -->
            <div class="mt-4 flex justify-end gap-4">
                <!-- Cancel Button -->
                <flux:button as="a" href="{{ route('attendance.index') }}" size="sm">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" size="sm">
                    Save Changes
                </flux:button>
                <!-- Delete Button (Triggers Separate Form) -->
                <flux:button variant="danger" size="sm" onclick="confirmDelete({{ $attendance->id }})">
                    Delete This Record
                </flux:button>
            </div>
        </form>
        <!-- Delete Button -->
        <form id="delete-form-{{ $attendance->id }}" action="{{ route('attendance.destroy', $attendance->id) }}"
            method="POST">
            @csrf
            @method('DELETE')
        </form>
    </div>
    <script>
        function confirmDelete(attendanceId) {
            if (confirm('Are you sure you want to delete this record?')) {
                document.getElementById('delete-form-' + attendanceId).submit();
            }
        }
    </script>
</x-layouts.app>
