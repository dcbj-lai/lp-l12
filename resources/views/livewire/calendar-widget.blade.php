<div x-data="{ show: true, ...countdown() }" x-init="startCountdown()" x-show="show"
    class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800">
    <button @click="show = false"
        class="absolute top-2 right-2 text-orange-400 opacity-50 hover:opacity-100 hover:text-orange-600 dark:hover:text-orange-300 transition-opacity">
        &times;
    </button>
    <h2 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">
        LAIC Calendar
    </h2>

    @forelse ($events as $event)
        <div class="mb-3 p-1 border border-gray-200 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-800 dark:text-gray-100">{{ $event['title'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ \Carbon\Carbon::parse($event['start'])->format('M d, Y h:i A') }}
                    </p>
                </div>
                <a href="{{ $event['link'] }}" target="_blank" class="text-blue-600 dark:text-blue-400">
                    <flux:icon name="calendar-days" class="w-5 h-5" />
                </a>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">No upcoming events.</p>
    @endforelse
    <div class="text-center absolute bottom-4 right-4">
        <flux:button size="xs" variant="ghost" href="#">
            See all...
        </flux:button>
    </div>
</div>
