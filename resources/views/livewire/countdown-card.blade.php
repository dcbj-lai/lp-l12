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
            <template x-for="([index, value]) in Object.entries(formattedTime.split(':'))" :key="index">
                <div class="flex flex-col items-center">
                    <div class="relative w-16 h-16">
                        <svg class="w-full h-full" viewBox="0 0 100 100">
                            <circle class="text-gray-300" stroke-width="10" stroke="currentColor" fill="transparent"
                                r="40" cx="50" cy="50" />
                            <circle :stroke="['#4a90e2', '#50e3c2', '#f5a623', '#d0021b'][index]" stroke-width="10"
                                fill="transparent" r="40" cx="50" cy="50" stroke-dasharray="251.2"
                                :stroke-dashoffset="251.2 - (value / [30, 24, 60, 60][index] * 251.2)"
                                stroke-linecap="round" transform="rotate(-90 50 50)" />
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold"
                            x-text="value"></span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-200"
                        x-text="['Days', 'Hours', 'Minutes', 'Seconds'][index]"></span>
                </div>
            </template>

        </div>
    </div>
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
</div>
