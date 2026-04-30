<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>App Launcher</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=crimson-pro:400,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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

        .dark .circuit-bg {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        }

        .card {
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
        }

        .dark .card {
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.6);
        }

        /* ✅ App Card (NO borders, NO pseudo elements) */
        .app-card {
            border-radius: 0.75rem;
            background-color: white;
            transition: all 0.25s ease;
            border: none !important;
            outline: none !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .dark .app-card {
            background-color: rgb(39 39 42);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
        }

        /* 🔥 REAL glow (no fake borders) */
        .app-card:hover {
            transform: translateY(-5px) scale(1.04);
            box-shadow:
                0 14px 40px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(158, 29, 32, 0.2),
                0 0 20px rgba(158, 29, 32, 0.35);
        }

        /* Icon animation */
        .app-card img {
            transition: transform 0.25s ease, filter 0.25s ease;
        }

        .app-card:hover img {
            transform: scale(1.1);
            filter: drop-shadow(0 0 8px rgba(158, 29, 32, 0.6));
        }

        .dark .app-card img {
            filter: brightness(1.1);
        }

        /* Kill browser focus garbage */
        .app-card:focus,
        .app-card:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-white dark:bg-zinc-900 flex items-center justify-center p-6">

    <!-- CARD -->
    <div
        class="w-full max-w-md bg-[#F2E8DC] dark:bg-zinc-900 border border-[#C9C9C9] dark:border-zinc-700 rounded-xl overflow-hidden card">

        <!-- Content -->
        <div class="p-8 circuit-bg">

            <!-- Header -->
            <div class="text-center text-[#9E1D20] serif mb-6 flex flex-col items-center gap-3">

                <!-- Logo -->
                <img src="https://life.edu.ph/wp-content/uploads/2026/04/LCI-HorizontalStack.png" alt="LIFE College"
                    class="w-32 h-auto object-contain">

                <!-- Title -->
                <div class="text-2xl font-bold tracking-tight">
                    App Launcher
                </div>

                <div class="text-sm opacity-70">
                    Quick access to your tools
                </div>

            </div>

            <!-- Divider -->
            <div class="mb-6 border-t border-[#C9C9C9] dark:border-zinc-700"></div>

            <!-- App Grid -->
            <div class="grid grid-cols-2 gap-4">

                <!-- AWS -->
                <a href="https://accounts.google.com/o/saml2/initsso?idpid=C03nwmmpc&spid=709701854053&forceauthn=true"
                    target="_blank" class="app-card flex flex-col items-center justify-center gap-2 p-5">

                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/Amazon_Web_Services_Logo.svg"
                        class="w-14 h-14 object-contain">

                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">
                        AWS
                    </span>
                </a>

                <!-- LCI Canvas -->
                <a href="https://lifeacademy.instructure.com/" target="_blank"
                    class="app-card flex flex-col items-center justify-center gap-2 p-5">

                    <img src="https://www.instructure.com/sites/default/files/image/2026-04/home-heroslide-canvastiers-v1.png"
                        class="w-14 h-14 object-contain">

                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">
                        LCI Canvas
                    </span>
                </a>

                <!-- Edusuite -->
                <a href="https://life.edusuite.asia" target="_blank"
                    class="app-card flex flex-col items-center justify-center gap-2 p-5">

                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRF3u9tTm0Qga2_FgZujmf9gZyZYEIU5ztvmQ&s"
                        class="w-14 h-14 object-contain">

                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">
                        Edusuite
                    </span>
                </a>

            </div>

        </div>

    </div>

</body>

</html>
