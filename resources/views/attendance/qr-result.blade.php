<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst($status) }} - Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body
    class="flex items-center justify-center h-full bg-gradient-to-b from-slate-900 via-slate-800 to-slate-700 text-white">

    <div class="text-center space-y-6">
        @if ($status === 'success')
            <div class="mx-auto w-24 h-24 rounded-full bg-green-500/20 flex items-center justify-center animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-green-400" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        @else
            <div class="mx-auto w-24 h-24 rounded-full bg-red-500/20 flex items-center justify-center animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-red-400" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
        @endif

        <h1 class="text-2xl font-bold tracking-wide">
            {{ $status === 'success' ? 'Success!' : 'Error' }}
        </h1>

        <p class="text-lg text-neutral-300">
            {{ $message }}
        </p>

        <p class="text-sm text-neutral-500 mt-4">
            {{ now()->format('F j, Y • g:i A') }}
        </p>

        <a href="{{ route('attendance.show_qr', ['type' => 'check_in']) }}"
            class="inline-block mt-6 px-6 py-2 text-sm font-semibold bg-white text-slate-800 rounded-lg shadow hover:bg-neutral-200 transition">
            Back to QR Page
        </a>
    </div>

</body>

</html>
