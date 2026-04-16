<x-dashboard-card class="p-4">

    <div class="relative flex flex-col aspect-square">

        <!-- Title -->
        <div class="absolute top-4 left-4">
            <h2 class="text-sm font-semibold text-gray-400 flex items-center gap-1">
                <flux:icon name="wifi" class="w-4 h-4" />
                Speed Test
            </h2>
        </div>

        <!-- Content -->
        <div class="flex flex-col justify-center items-center flex-grow px-4 text-center">

            <!-- Latency -->
            <div class="flex flex-col items-center gap-1">
                <span class="text-gray-500 text-xs">Latency</span>

                <span class="text-3xl font-semibold text-gray-200">
                    {{ $latency ?? '--' }}
                </span>

                <span class="text-xs text-gray-500">ms</span>
            </div>

        </div>

        <!-- Footer -->
        <div class="absolute bottom-4 right-4">
            <flux:button size="xs" variant="ghost" icon="activity" wire:click="runLatency">
                Run Test
            </flux:button>
        </div>

    </div>
    @script
        <script>
            document.addEventListener('run-latency', async (event) => {
                try {
                    async function measureLatency(samples = 7) {
                        const options = {
                            cache: 'no-store',
                            keepalive: true,
                        };

                        // warm-up (stabilize connection + container)
                        for (let i = 0; i < 2; i++) {
                            await fetch('/api/ping', options);
                        }

                        let results = [];

                        for (let i = 0; i < samples; i++) {
                            const start = performance.now();

                            await fetch('/api/ping', options);

                            const duration = performance.now() - start;
                            results.push(duration);
                        }

                        // sort ascending
                        results.sort((a, b) => a - b);

                        // median
                        const mid = Math.floor(results.length / 2);
                        const median = results.length % 2 !== 0 ?
                            results[mid] :
                            (results[mid - 1] + results[mid]) / 2;

                        return median;
                    }

                    const latency = await measureLatency(7);

                    const component = Livewire.find(event.detail.componentId);
                    component.call('setLatency', latency);

                } catch (e) {
                    console.error('Latency test failed', e);
                }
            });
        </script>
    @endscript

</x-dashboard-card>
