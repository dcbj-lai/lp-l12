<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main class="space-y-4">

        <!-- Flash Messages -->
        <x-alert type="success" :message="session('success')" />
        <x-alert type="error" :message="session('error')" />
        <x-alert type="warning" :message="session('warning')" />
        <x-alert type="info" :message="session('info')" />

        {{ $slot }}

    </flux:main>
</x-layouts.app.sidebar>
