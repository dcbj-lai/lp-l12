<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main class="space-y-4">

        <!-- Flash Messages -->
        <x-alert :message="session('success') ?? (session('error') ?? (session('warning') ?? session('info')))" :type="session('success')
            ? 'success'
            : (session('error')
                ? 'error'
                : (session('warning')
                    ? 'warning'
                    : 'info'))" />

        {{ $slot }}

    </flux:main>
</x-layouts.app.sidebar>
