<x-layouts.app>
    <div class="max-w-3xl mx-auto mt-10 bg-white dark:bg-zinc-800 shadow rounded-lg p-6 space-y-6">
        <h2 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100 border-b pb-3">
            Add Attendance Record
        </h2>

        <form action="{{ route('attendance.store') }}" method="POST" class="space-y-4">
            @csrf

            {{-- Employee --}}
            <div>
                <label for="user_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Employee</label>
                <select name="user_id" id="user_id"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
                    <option value="">Select employee...</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('user_id') == $employee->id)>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Date --}}
            <div>
                <label for="date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Date</label>
                <input type="date" name="date" id="date" value="{{ old('date') }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
                @error('date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Check In --}}
            <div>
                <label for="check_in" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Check
                    In</label>
                <input type="time" name="check_in" id="check_in" value="{{ old('check_in') }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
                @error('check_in')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Check Out --}}
            <div>
                <label for="check_out" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Check
                    Out</label>
                <input type="time" name="check_out" id="check_out" value="{{ old('check_out') }}"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
                @error('check_out')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remarks --}}
            <div>
                <label for="remarks" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Remarks</label>
                <input type="text" name="remarks" id="remarks" value="{{ old('remarks') }}"
                    placeholder="Optional (e.g., Manual entry by HR)"
                    class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
                @error('remarks')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Buttons --}}
            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <a href="{{ route('attendance.index') }}"
                    class="px-3 py-2 text-sm rounded-md bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600">
                    Cancel
                </a>
                <button type="submit" class="px-3 py-2 text-sm rounded-md bg-blue-600 hover:bg-blue-700 text-white">
                    Save Attendance
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
