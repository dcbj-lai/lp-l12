{{-- resources/views/livewire/guidance/check-in-consultation.blade.php --}}

<div class="rounded-lg shadow bg-white dark:bg-gray-800 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Teacher Selection & Check-in
        </h2>

        <button type="button"
                wire:click="checkIn"
                wire:loading.attr="disabled"
                wire:target="checkIn"
                @disabled($checkedIn || empty($teacherEmail))
                class="inline-flex items-center rounded px-4 py-2 text-sm font-medium text-white transition
                       {{ ($checkedIn || empty($teacherEmail))
                            ? 'bg-gray-400 cursor-not-allowed'
                            : 'bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600' }}">
            <span wire:loading.remove wire:target="checkIn">Check-in / Send Notification</span>
            <span wire:loading wire:target="checkIn">Sending...</span>
        </button>
    </div>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Teacher selector --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                Select Current Teacher <span class="text-red-600">*</span>
            </label>

            <select
                wire:model="teacherEmail"
                wire:change="$set('teacherName', $event.target.selectedOptions[0].dataset.name)"
                @disabled($checkedIn)
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900
                       dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600
                       focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                <option value="">-- Select teacher --</option>

                @foreach($teachers as $t)
                    <option value="{{ $t['email'] }}" data-name="{{ $t['name'] }}">
                        {{ $t['name'] }}
                    </option>
                @endforeach

                <option value="">No teacher available</option>
            </select>

            @error('teacherEmail')
                <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
            @enderror

            @error('teacherName')
                <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
            @enderror

            @if($checkedIn)
                <p class="mt-2 text-xs text-green-600">✅ Checked in.</p>
            @else
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Teacher selection is required before check-in and before the form is enabled.
                </p>
            @endif
        </div>

        {{-- Time cards --}}
        <div class="grid grid-cols-1 gap-4">
            <div class="rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Time In</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $timeInDisplay ?? '—' }}
                </div>
            </div>

            <div class="rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Time Out</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">—</div>
            </div>
        </div>
    </div>
</div>