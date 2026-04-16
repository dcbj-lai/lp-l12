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
                    async function measureLatency(samples = 5) {
                        // warm-up (connection setup)
                        await fetch('/api/ping', {
                            cache: 'no-store',
                            keepalive: true
                        });

                        let results = [];

                        for (let i = 0; i < samples; i++) {
                            const start = performance.now();

                            await fetch('/api/ping', {
                                cache: 'no-store',
                                keepalive: true,
                            });

                            results.push(performance.now() - start);
                        }

                        // remove worst outlier (Laravel spike)
                        results.sort((a, b) => a - b);
                        results.pop(); // remove highest

                        const avg = results.reduce((a, b) => a + b, 0) / results.length;

                        return avg;
                    }

                    const latency = await measureLatency(5);

                    const component = Livewire.find(event.detail.componentId);
                    component.call('setLatency', latency);

                } catch (e) {
                    console.error('Latency test failed', e);
                }
            });
        </script>
    @endscript

</x-dashboard-card>
