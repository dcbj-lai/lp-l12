<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $user->preferred_name ?: $user->name }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .hover-lift {
            transition: all .25s ease;
        }

        .hover-lift:hover {
            transform: translateY(-3px) scale(1.02);
        }

        /* subtle glow */
        .card-glow {
            box-shadow:
                0 4px 20px rgba(0, 0, 0, 0.08),
                0 0 0 rgba(128, 0, 0, 0);
            transition: box-shadow .3s ease;
        }

        .card-glow:hover {
            box-shadow:
                0 6px 30px rgba(0, 0, 0, 0.12),
                0 0 18px rgba(128, 0, 0, 0.18);
        }

        /* glowing maroon grid */
        .circuit-bg {
            background-image:
                linear-gradient(rgba(128, 0, 0, 0.12) 1px, transparent 1px),
                linear-gradient(90deg, rgba(128, 0, 0, 0.12) 1px, transparent 1px);
            background-size: 26px 26px;

            /* subtle glow effect */
            box-shadow: inset 0 0 40px rgba(128, 0, 0, 0.06);
        }

        /* dark mode tuning */
        .dark .circuit-bg {
            background-image:
                linear-gradient(rgba(220, 20, 60, 0.18) 1px, transparent 1px),
                linear-gradient(90deg, rgba(220, 20, 60, 0.18) 1px, transparent 1px);
        }
    </style>
</head>

<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-stone-900 dark:text-stone-100 flex flex-col">

    <main class="flex-1 w-full max-w-6xl mx-auto flex flex-col items-center justify-center p-6 lg:p-8">

        <!-- Profile Card -->
        <div
            class="w-full max-w-md 
            bg-white dark:bg-zinc-800 
            border border-zinc-200 dark:border-zinc-700
            rounded-2xl overflow-hidden
            hover-lift card-glow">

            <!-- Accent Strip -->
            <div class="h-2 bg-[#800000]"></div>

            <!-- Content -->
            <div class="px-6 pb-6 pt-6 circuit-bg">

                <!-- School -->
                <div class="text-center mb-3">
                    <div class="text-xs tracking-wide uppercase text-[#800000] font-semibold">
                        Life College International
                    </div>
                </div>

                <!-- Avatar -->
                <div class="mb-4 flex justify-center">
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

                    <!-- ALWAYS SHOW POSITION (fallback safe) -->
                    <!-- Position -->
                    <div class="mt-2 text-sm">
                        <span
                            class="{{ $user->position ? 'text-stone-700 dark:text-stone-300' : 'text-transparent select-none' }}">
                            {{ $user->position ?? '--' }}
                        </span>
                    </div>

                    <!-- Department -->
                    <div class="text-sm">
                        <span
                            class="{{ $user->department?->name ? 'text-stone-500 dark:text-stone-400' : 'text-transparent select-none' }}">
                            {{ $user->department->name ?? '--' }}
                        </span>
                    </div>
                </div>

                <!-- Action -->
                <div class="mt-6">
                    <a href="{{ route('card.vcard', ['slug' => request()->route('slug')]) }}"
                        class="block w-full text-center 
                        bg-[#800000] hover:bg-[#5c0000]
                        text-white py-2 rounded-lg font-medium transition
                        shadow-[0_0_10px_rgba(128,0,0,0.25)]
                        hover:shadow-[0_0_20px_rgba(128,0,0,0.35)]">
                        Save Contact
                    </a>
                </div>

            </div>
        </div>

    </main>

    <footer class="border-t border-zinc-200 dark:border-zinc-700 bg-stone-50 dark:bg-stone-900 w-full py-6 mt-auto">
        <div class="flex justify-center text-sm text-stone-700 dark:text-stone-300">
            &copy; {{ date('Y') }} Life College International
        </div>
    </footer>

</body>

</html>
