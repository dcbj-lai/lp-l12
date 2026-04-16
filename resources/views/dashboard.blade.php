<x-layouts.app title="Dashboard">
    <div class="flex h-full w-full flex-1 flex-col rounded-xl relative">

        <!-- Cards -->
        <div class="grid auto-rows-min md:grid-cols-4 gap-4">
            <livewire:feature-verse />
            <livewire:celebrations-card />
            <livewire:steps-leaderboard mode="card" />
            <livewire:speed-test-card />
        </div>

        <!-- ✅ Gradient Fade (mobile only) -->
        <div
            class="md:hidden fixed bottom-0 left-0 w-full h-16 
                   bg-gradient-to-t from-white/80 to-transparent 
                   dark:from-zinc-900/80 pointer-events-none z-40">
        </div>

        <!-- ✅ Scroll Hint (Top) -->
        <div x-data="{
            atBottom: false,
            init() {
                const el = document.documentElement;
                window.addEventListener('scroll', () => {
                    this.atBottom = (window.innerHeight + window.scrollY) >= (el.scrollHeight - 10);
                });
            }
        }"
            class="md:hidden fixed top-4 left-0 w-full flex justify-center z-50 pointer-events-none">
            <div
                class="pointer-events-none flex items-center gap-2 px-3 py-1 rounded-full 
       bg-yellow-200/40 dark:bg-yellow-300/20 
       backdrop-blur-md 
       text-yellow-700 dark:text-yellow-200 
       shadow-[0_0_10px_rgba(250,204,21,0.4)]">

                <flux:icon name="arrow-down"
                    class="w-3 h-3 transition-transform duration-300 text-yellow-600 dark:text-yellow-200"
                    x-bind:class="{ 'rotate-180': atBottom }" />

                <span x-text="atBottom ? 'Scroll up' : 'Scroll'"></span>
            </div>
        </div>

    </div>
</x-layouts.app>
