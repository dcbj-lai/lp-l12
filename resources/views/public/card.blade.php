<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $user->preferred_name ?: $user->name }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .hover-lift {
            transition: all .25s ease;
        }

        .hover-lift:hover {
            transform: translateY(-3px) scale(1.02);
        }
    </style>
</head>

<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-stone-900 dark:text-stone-100 flex flex-col">

    <!-- Header -->
    <header class="w-full max-w-6xl mx-auto py-6 px-6 lg:px-8 mb-4">
        <nav class="flex justify-end gap-3 text-sm">
            @auth
                <flux:button tag="a" href="{{ route('dashboard') }}" size="sm" variant="ghost"
                    class="text-stone-800 dark:text-stone-200 border border-zinc-300 dark:border-zinc-700
                    hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    Dashboard
                </flux:button>
            @else
                <flux:button tag="a" href="{{ route('login') }}" size="sm" variant="outline">
                    Login
                </flux:button>
            @endauth
        </nav>
    </header>

    <!-- Main -->
    <main class="flex-1 w-full max-w-6xl mx-auto flex flex-col items-center justify-center p-6 lg:p-8">

        <!-- Profile Card -->
        <div
            class="w-full max-w-md 
            bg-white dark:bg-zinc-800 
            border border-zinc-200 dark:border-zinc-700
            rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.08)]
            overflow-hidden hover-lift">

            <!-- Cover -->
            <div class="h-24 bg-gradient-to-r from-[#00D2FF] to-[#3A7BD5]"></div>

            <!-- Content -->
            <div class="px-6 pb-6">

                <!-- Avatar -->
                <div class="-mt-12 mb-4 flex justify-center">
                    <div
                        class="h-24 w-24 rounded-full overflow-hidden border-4 border-white dark:border-zinc-800 shadow">

                        @if ($user->profile_photo_path)
                            <img src="{{ Storage::disk('s3')->url($user->profile_photo_path) }}"
                                class="h-full w-full object-cover">
                        @else
                            <div
                                class="h-full w-full flex items-center justify-center 
                                bg-zinc-200 dark:bg-zinc-700 text-lg font-semibold">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif

                    </div>
                </div>

                <!-- Name -->
                <div class="text-center">
                    <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-100">
                        {{ $user->preferred_name ?: $user->name }}
                    </h1>

                    @if ($user->preferred_name)
                        <div class="text-sm text-stone-500 dark:text-stone-400">
                            {{ $user->name }}
                        </div>
                    @endif

                    <!-- Position -->
                    @if ($user->position)
                        <div class="mt-2 text-sm text-stone-700 dark:text-stone-300">
                            {{ $user->position }}
                        </div>
                    @endif

                    <!-- Department -->
                    @if ($user->department)
                        <div class="text-sm text-stone-500 dark:text-stone-400">
                            {{ $user->department->name }}
                        </div>
                    @endif
                </div>

                <!-- Action -->
                <div class="mt-6">
                    <a href="{{ route('card.vcard', ['slug' => request()->route('slug')]) }}"
                        class="block w-full text-center 
   bg-[#1490B4] hover:bg-[#11728f]
   text-white py-2 rounded-lg font-medium transition">
                        Save Contact
                    </a>
                </div>

            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-zinc-200 dark:border-zinc-700 bg-stone-50 dark:bg-stone-900 w-full py-6 mt-auto">
        <div class="flex flex-wrap justify-center gap-3 text-sm text-stone-700 dark:text-stone-300">
            <span>&copy; {{ date('Y') }} LAI College. All rights reserved.</span>
        </div>
    </footer>

</body>

</html>
