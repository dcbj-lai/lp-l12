<x-layouts.app title="Edit Online Day">
    <a href="{{ route('onlinedays.index') }}"
        class="inline-block mb-4 px-3 py-2 rounded-md bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600">
        ← Back
    </a>

    <h1 class="text-2xl font-semibold mb-4">Edit Online Day</h1>

    <form method="POST" action="{{ route('onlinedays.update', $onlineDay) }}" class="space-y-4 max-w-lg">
        @csrf
        @method('PUT')

        <!-- Date -->
        <div>
            <label for="date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Date</label>
            <input type="date" id="date" name="date" required value="{{ $onlineDay->date->format('Y-m-d') }}"
                class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
        </div>

        <!-- Declared By -->
        <div>
            <label for="declared_by" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Declared
                By</label>
            <input type="text" id="declared_by" name="declared_by" required value="{{ $onlineDay->declared_by }}"
                class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
        </div>

        <!-- Remarks -->
        <div>
            <label for="remarks" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Remarks</label>
            <input type="text" id="remarks" name="remarks" value="{{ $onlineDay->remarks }}"
                class="w-full border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
        </div>

        <!-- Active -->
        <div class="flex items-center space-x-2">
            <input type="checkbox" id="is_active" name="is_active" value="1" {{ $onlineDay->is_active ? 'checked' : '' }}>
            <label for="is_active">Active</label>
        </div>

        <!-- Submit -->
        <div>
            <button type="submit" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm">
                Update
            </button>
        </div>
    </form>
</x-layouts.app>
