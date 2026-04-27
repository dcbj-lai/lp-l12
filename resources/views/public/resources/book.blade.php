<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Book a Resource</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=crimson-pro:400,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        .serif {
            font-family: "Crimson Pro", serif;
        }

        .circuit-bg {
            background-image:
                linear-gradient(rgba(158, 29, 32, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(158, 29, 32, 0.04) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .card {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>

<body class="min-h-screen bg-white flex items-center justify-center p-6 dark:bg-zinc-900">

    <!-- CARD -->
    <div
        class="w-full max-w-md bg-[#F2E8DC] dark:bg-zinc-900 border border-[#C9C9C9] dark:border-zinc-700 rounded-xl overflow-hidden card">

        <!-- Accent -->
        <div class="h-[3px] bg-[#9E1D20]"></div>

        <!-- Content -->
        <div class="p-8 circuit-bg">

            <!-- Header -->
            <div class="text-center text-[#9E1D20] serif mb-6">
                <div class="text-2xl font-bold leading-tight tracking-tight">
                    Resource Booking
                </div>

                <div class="text-sm opacity-70">
                    Reserve rooms or equipment
                </div>
            </div>

            <!-- Divider -->
            <div class="mb-6 border-t border-[#C9C9C9]"></div>

            <!-- Livewire Form -->
            <livewire:resources.create-reservation />

        </div>

    </div>

    @livewireScripts
</body>

</html>
