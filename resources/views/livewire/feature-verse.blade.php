<div x-data="{ show: true, menuOpen: false }" x-show="show" {{--
    class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800"> --}}
    class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800 flex flex-col justify-center
    aspect-square">
    @can('is-admin')
        <!-- Ellipsis Dropdown Button -->
        <div class="absolute top-2 right-2">
            <button @click="menuOpen = !menuOpen"
                class="text-orange-400 opacity-50 hover:opacity-100 hover:text-orange-600 transition-opacity">
                &#x22EE;
            </button>
            <div x-show="menuOpen" @click.away="menuOpen = false"
                class="absolute right-0 mt-2 w-24 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg overflow-hidden">
                <button @click="$wire.set('editing', true); menuOpen = false"
                    class="block w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Edit</button>
            </div>
        </div>
    @endcan
    <!-- Bible Verse Content -->
    <div
        class="text-center text-transparent bg-gradient-to-r from-sky-500 via-blue-500 to-orange-500 bg-clip-text font-semibold">
        @if ($editing)
            <textarea wire:model="verse"
                class="backdrop-blur-md w-full bg-transparent border border-gray-300 focus:outline-none focus:border-blue-500 rounded-md p-2 caret-gray-600 dark:caret-gray-200 font-normal text-gray-500 dark:text-gray-100"></textarea>
            <input type="text" wire:model="reference"
                class="mt-2 w-full bg-transparent border-b border-gray-300 focus:outline-none focus:border-blue-500 text-sm caret-gray-600 dark:caret-gray-200 font-normal text-gray-500 dark:text-gray-100">
        @else
            <p class="text-lg">{{ $verse }}</p>
            <p class="mt-2 text-sm font-semibold text-gray-400">{{ $reference }}</p>
        @endif
    </div>

    @can('is-admin')
        <div class="flex space-x-2 mt-2 justify-center">
            @if ($editing)
                <flux:button wire:click="saveVerse" size="xs" variant="ghost">
                    Save
                </flux:button>
                <flux:button wire:click="$set('editing', false)" size="xs">
                    Cancel
                </flux:button>
            @endif
        </div>
    @endcan
</div>
