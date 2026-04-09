<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main class="space-y-4">

        <!-- Flash Messages -->
        <x-alert :message="session('flash.message')" :type="session('flash.type', 'success')" />

        {{ $slot }}

    </flux:main>
</x-layouts.app.sidebar>
