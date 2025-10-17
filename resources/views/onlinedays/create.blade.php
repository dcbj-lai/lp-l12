<x-layouts.app title="Add Online Day">
    <a href="{{ route('onlinedays.index') }}"
        class="inline-block mb-4 px-3 py-2 rounded-md bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600">
        ← Back
    </a>
    <h1 class="text-2xl font-semibold mb-4">Add Online Day</h1>

    <form method="POST" action="{{ route('onlinedays.store') }}" class="space-y-4 max-w-lg">
        @csrf
        <flux:input label="Date" name="date" type="date" required />
        <flux:input label="Declared By" name="declared_by" required />
        <flux:input label="Remarks" name="remarks" />
        <label class="flex items-center space-x-2">
            <input type="checkbox" name="is_active" value="1" checked>
            <span>Active</span>
        </label>
        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
</x-layouts.app>
