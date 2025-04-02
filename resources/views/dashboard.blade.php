<x-layouts.app title="Dashboard">
    <div class="flex h-full w-full flex-1 flex-col rounded-xl">
        <div class="grid auto-rows-min md:grid-cols-2 gap-4"> <!-- gap-0 removes space -->
            <!-- Bible Verse Card -->
            <div>
                <livewire:feature-verse />

            </div>
            <!-- Countdown Card -->
            <div x-data="{ show: true, ...countdown() }" x-init="startCountdown()" x-show="show"
                class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800">

                <button @click="show = false"
                    class="absolute top-2 right-2 text-orange-400 opacity-50 hover:opacity-100 hover:text-orange-600 dark:hover:text-orange-300 transition-opacity">
                    &times;
                </button>
                <h2 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">
                    <flux:icon.clock class="w-5 h-5 inline" /> Classes Open
                </h2>
                </button>
                <div x-data="countdown()" x-init="startCountdown()" class="flex flex-col items-center space-y-4">
                    <div class="flex space-x-4">
                        <template x-for="(value, index) in formattedTime.split(':')" :key="index">
                            <div class="flex flex-col items-center">
                                <div class="relative w-16 h-16">
                                    <svg class="w-full h-full" viewBox="0 0 100 100">
                                        <circle class="text-gray-300" stroke-width="10" stroke="currentColor"
                                            fill="transparent" r="40" cx="50" cy="50" />
                                        <circle :stroke="['#4a90e2', '#50e3c2', '#f5a623', '#d0021b'][index]"
                                            stroke-width="10" fill="transparent" r="40" cx="50" cy="50"
                                            stroke-dasharray="251.2"
                                            :stroke-dashoffset="251.2 - (value / [30, 24, 60, 60][index] * 251.2)"
                                            stroke-linecap="round" transform="rotate(-90 50 50)" />
                                    </svg>
                                    <span
                                        class="absolute inset-0 flex items-center justify-center text-lg font-semibold"
                                        x-text="value"></span>
                                </div>
                                <span class="text-sm text-gray-600"
                                    x-text="['Days', 'Hours', 'Minutes', 'Seconds'][index]"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Ripple Feed -->
            <div x-data="{ show: true }" x-show="show"
                class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800">
                <button @click="show = false"
                    class="absolute top-2 right-2 text-orange-400 opacity-50 hover:opacity-100 hover:text-orange-600 dark:hover:text-orange-300 transition-opacity">
                    &times;
                </button>

                <h2 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">
                    <flux:icon.pin class="w-5 h-5 inline" /> Pinned Ripples
                </h2>

                @if($pinnedRipples->isEmpty())
                    <p class="text-gray-500 dark:text-gray-400">No pinned ripples yet.</p>
                @else
                    <ul class="space-y-2 mb-3">
                        @foreach($pinnedRipples as $ripple)
                            <li class="p-2 bg-yellow-50 dark:bg-yellow-300 rounded-md shadow-md">
                                <strong class="text-gray-800 dark:text-white">{{ $ripple->user->name }}</strong>
                                <p class="text-sm text-gray-700 dark:text-gray-500">{{ Str::limit($ripple->content, 100) }}</p>
                                <span
                                    class="text-xs text-gray-500 dark:text-gray-400">{{ $ripple->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('ripple') }}"
                        class="text-blue-500 dark:text-blue-400 hover:underline text-sm font-semibold">
                        ➜ View All Ripples
                    </a>
                @endif
            </div>
            {{-- Steps leaderboard --}}
            <div x-data="{ show: true }" x-show="show"
                class="dashboard-card p-4 rounded-lg shadow-md bg-white dark:bg-gray-800 relative">
                <!-- Close button -->
                <button @click="show = false"
                    class="absolute top-2 right-2 text-orange-400 opacity-50 hover:opacity-100 hover:text-orange-600 dark:hover:text-orange-300 transition-opacity">
                    &times;
                </button>

                <!-- Card Title -->
                <h2 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                    <flux:icon name="award" class="h-5 w-5 inline" />Top 3 Step Champions
                </h2>

                <!-- Leaderboard List -->
                <ul class="space-y-3">
                    @forelse ($topUsers as $index => $user)
                        <li class="p-3 bg-gray-50 dark:bg-gray-700 rounded-md shadow flex justify-between items-center">
                            <span class="text-gray-800 dark:text-gray-200 font-medium">
                                @if($index === 0)
                                    <flux:icon name="trophy" class="text-yellow-500 h-5 w-5 inline" />
                                @elseif($index === 1)
                                    <flux:icon name="medal" class="text-gray-400 h-5 w-5 inline" />
                                @elseif($index === 2)
                                    <flux:icon name="award" class="text-orange-500 h-5 w-5 inline" />
                                @else
                                    {{ $index + 1 }}
                                @endif
                                #{{ $index + 1 }} {{ $user->user->name }}
                            </span>
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ number_format($user->total_steps) }} steps
                            </span>
                        </li>
                    @empty
                        <li class="text-gray-500 dark:text-gray-400 text-center">
                            No steps logged yet — time to move! 🚶‍♂️
                        </li>
                    @endforelse
                </ul>

                <!-- Full Leaderboard Link -->
                <a href="{{ route('steps.index') }}"
                    class="text-blue-500 dark:text-blue-400 hover:underline text-sm font-semibold mt-4 inline-block">
                    ➜ View Full Leaderboard
                </a>
            </div>



        </div>
    </div>
</x-layouts.app>



<!-- Scripts -->
<script>
    function countdown() {
        return {
            formattedTime: "00:00:00:00",
            startCountdown() {
                const targetDate = new Date('2025-08-04T00:00:00');

                const updateCountdown = () => {
                    const now = new Date();
                    const diffTime = targetDate - now;

                    if (diffTime <= 0) {
                        this.formattedTime = "00:00:00:00";
                        return;
                    }

                    const days = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diffTime % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diffTime % (1000 * 60)) / 1000);

                    this.formattedTime = `${String(days).padStart(2, '0')}:` +
                        `${String(hours).padStart(2, '0')}:` +
                        `${String(minutes).padStart(2, '0')}:` +
                        `${String(seconds).padStart(2, '0')}`;
                };

                updateCountdown();
                setInterval(updateCountdown, 1000);
            }
        };
    }
</script>

<!-- Styling with Hover Effect -->
<style>
    .dashboard-card {
        position: relative;
        color: #1a202c;
        background-color: oklch(0.985 0 0);
        /* Soft yellow */
        border-radius: 0.75rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        min-height: 150px;
        transition: all 0.3s ease-in-out;
        overflow: hidden;
    }

    /* Dark mode adaptation */
    .dark .dashboard-card {
        color: #e2e8f0;
        background-color: oklch(0.371 0 0);
        border-color: #4a5568;
    }

    /* Hover effect */
    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        filter: brightness(1.05);
    }
</style>
