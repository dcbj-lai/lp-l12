<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
    <!-- Flash Messages -->
    <div class="w-full md:w-auto">
        <x-alert type="success" :message="session('success')" />
        <x-alert type="error" :message="session('error')" />
        <x-alert type="warning" :message="session('warning')" />
        <x-alert type="info" :message="session('info')" />
    </div>
</x-layouts.app.sidebar>
