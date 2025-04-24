<div x-data="{ show: true, ...countdown() }" x-init="startCountdown()" x-show="show"
    class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800">
    <button @click="show = false"
        class="absolute top-2 right-2 text-orange-400 opacity-50 hover:opacity-100 hover:text-orange-600 dark:hover:text-orange-300 transition-opacity">
        &times;
    </button>
    <h2 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">
        <flux:icon.footprints class="w-5 h-5 inline" /> Top Steppers
    </h2>

    @if ($leaders->isEmpty())
        <p class="text-gray-600 dark:text-gray-300 text-xs text-center">No steps logged this month yet.</p>
    @else
        <ul class="space-y-2">
            @foreach ($leaders as $index => $entry)
                <li class="flex justify-between items-center text-sm">
                    <span class="text-gray-700 dark:text-gray-200">
                        {{ $index + 1 }}. {{ $entry->user->name }}
                    </span>
                    <span class="text-blue-600 font-bold dark:text-blue-400">
                        {{ number_format($entry->total_steps) }} steps
                    </span>
                    <span>
                        @if($index === 0)
                            <flux:icon name="trophy" class="text-yellow-500 h-5 w-5" />
                        @elseif($index === 1)
                            <flux:icon name="medal" class="text-gray-400 h-5 w-5" />
                        @elseif($index === 2)
                            <flux:icon name="award" class="text-orange-500 h-5 w-5" />
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
    <div class="text-center absolute bottom-4 right-4">
        <flux:button size="xs" variant="ghost" href="{{ route('steps.index') }}">
            See all...
        </flux:button>
    </div>
</div>
