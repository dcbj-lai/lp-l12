<div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Teacher Selection & Check-in</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select the current teacher if applicable, then record Time In.</p>
        </div>

        <button type="button" wire:click="checkIn" wire:loading.attr="disabled" wire:target="checkIn" @disabled($checkedIn)
                class="inline-flex items-center rounded px-4 py-2 text-sm font-medium text-white transition {{ $checkedIn ? 'cursor-not-allowed bg-gray-400' : 'bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600' }}">
            <span wire:loading.remove wire:target="checkIn">Check-in</span>
            <span wire:loading wire:target="checkIn">Checking in...</span>
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Select Current Teacher (Optional)</label>
            <select wire:model="teacherEmail"
                    wire:change="$set('teacherName', $event.target.selectedOptions[0]?.dataset.name ?? '')"
                    @disabled($checkedIn)
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">-- No teacher selected --</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher['email'] }}" data-name="{{ $teacher['name'] }}">{{ $teacher['name'] }}</option>
                @endforeach
            </select>

            @error('teacherEmail')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
            @error('teacherName')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror

            <p class="mt-2 text-xs {{ $checkedIn ? 'text-green-600' : 'text-gray-500 dark:text-gray-400' }}">
                {{ $checkedIn ? 'Checked in.' : 'Only active users with the Faculty role appear in this list.' }}
            </p>
        </div>

        <div class="rounded border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Time In</div>
            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $timeInDisplay ?? '—' }}</div>
        </div>
    </div>
</div>