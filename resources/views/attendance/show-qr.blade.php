<x-layouts.app title="Faculty Attendance QR">
    <div x-data="{
            expiresAtMs: {{ $token->expires_at->getTimestamp() * 1000 }},
            remaining: 0,
            timer: null,
            getRemaining() {
                const secs = Math.ceil((this.expiresAtMs - Date.now()) / 1000);
                return Math.max(0, secs);
            },
            start() {
                this.remaining = this.getRemaining();
                if (this.remaining <= 0) {
                    window.location.reload();
                    return;
                }
                this.timer = setInterval(() => {
                    this.remaining = this.getRemaining();
                    if (this.remaining <= 0) {
                        clearInterval(this.timer);
                        setTimeout(() => window.location.reload(), 250);
                    }
                }, 1000);
            }
        }" x-init="start()" class="max-w-2xl mx-auto py-10 text-center">

        <h1 class="text-2xl font-bold mb-6">Faculty Attendance QR Code</h1>

        {{-- 🔄 Toggle Button --}}
        <div class="mb-6">
            <a href="{{ route('attendance.show_qr', ['type' => $type === 'check_in' ? 'check_out' : 'check_in']) }}"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                Switch to {{ $type === 'check_in' ? 'Check Out' : 'Check In' }}
            </a>
        </div>

        {{-- 🌈 Type Indicator --}}
        <div class="mb-6 flex justify-center">
            @if ($type === 'check_in')
                <div
                    class="px-6 py-2 rounded-full bg-green-500/20 text-green-700 dark:text-green-400 font-semibold border border-green-400 animate-pulse">
                    ✅ Check In Mode
                </div>
            @else
                <div
                    class="px-6 py-2 rounded-full bg-red-500/20 text-red-700 dark:text-red-400 font-semibold border border-red-400 animate-pulse">
                    🚪 Check Out Mode
                </div>
            @endif
        </div>

        {{-- 🧾 QR Card --}}
        <div class="bg-white dark:bg-neutral-800 shadow-md rounded-lg p-6">
            <p class="mb-4 text-neutral-600 dark:text-neutral-300">
                Scan this QR code to <strong>{{ ucfirst(str_replace('_', ' ', $type)) }}</strong>.<br>
                Token expires in: <strong x-text="remaining + ' seconds'"></strong>
            </p>

            <img src="data:image/svg+xml;base64,{{ $qrImage }}" alt="QR Code" class="mx-auto mb-4 w-64 h-64">

            <p class="text-sm text-neutral-500 break-all">
                URL: <a href="{{ $qrUrl }}" class="text-blue-500">{{ $qrUrl }}</a>
            </p>
        </div>
    </div>
</x-layouts.app>
