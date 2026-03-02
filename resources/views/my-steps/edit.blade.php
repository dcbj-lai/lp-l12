<x-layouts.app title="Edit Steps">
    <div class="max-w-2xl mx-auto py-10 px-6">

        <h1 class="text-lg font-bold mb-6">
            Edit Step Log
        </h1>

        <form action="{{ route('my-steps.update', $step) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- Date (Read Only) --}}
            <div>
                <label class="block mb-1 font-semibold">Date</label>

                <input type="text" value="{{ \Carbon\Carbon::parse($step->date)->format('M d, Y') }}"
                    class="w-full border p-2 rounded bg-gray-100 dark:bg-gray-800 cursor-not-allowed" disabled>

                {{-- Hidden field to preserve date --}}
                <input type="hidden" name="date" value="{{ $step->date }}">
            </div>

            {{-- Steps --}}
            <div>
                <label class="block mb-1 font-semibold">Steps</label>
                <input type="number" name="steps" value="{{ old('steps', $step->steps) }}"
                    class="w-full border p-2 rounded" min="1" required>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Update Steps
                </button>

                <a href="{{ route('my-steps.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</x-layouts.app>
