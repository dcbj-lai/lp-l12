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

        .app-card:hover {
            transform: translateY(-5px) scale(1.04);
            box-shadow:
                0 14px 40px rgba(0, 0, 0, 0.15),
                0 0 20px rgba(158, 29, 32, 0.35);
        }

        .icon-wrap {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-wrap img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: all 0.25s ease;
        }

        .app-card:hover img {
            transform: scale(1.1);
            filter: drop-shadow(0 0 6px rgba(158, 29, 32, 0.5));
        }

        .dark .app-card img {
            filter: brightness(1.1);
        }

        .app-card:focus,
        .app-card:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-white dark:bg-zinc-900 flex items-center justify-center p-6">

    <div
        class="w-full max-w-lg bg-[#F2E8DC] dark:bg-zinc-900 border border-[#C9C9C9] dark:border-zinc-700 rounded-xl overflow-hidden card">

        <div class="p-8 circuit-bg">

            <!-- Header -->
            <div class="text-center text-[#9E1D20] serif mb-6 flex flex-col items-center gap-3">

                <img src="https://life.edu.ph/wp-content/uploads/2026/04/LCI-HorizontalStack.png"
                    class="w-40 object-contain">

                <div class="text-2xl font-bold tracking-tight">
                    App Launcher
                </div>

                <div class="text-sm opacity-70">
                    Quick access to your tools
                </div>
            </div>

            <div class="mb-6 border-t border-[#C9C9C9] dark:border-zinc-700"></div>

            <!-- GRID -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">

                <!-- AWS Google SSO -->
                <a href="https://accounts.google.com/o/saml2/initsso?idpid=C03nwmmpc&spid=709701854053&forceauthn=true"
                    target="_blank" class="app-card flex flex-col items-center justify-center gap-2 p-5">

                    <div class="icon-wrap">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/Amazon_Web_Services_Logo.svg">
                    </div>

                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">
                        AWS SSO
                    </span>
                </a>

                <!-- Learn -->
                <a href="https://learn.stg.life.edu.ph" target="_blank"
                    class="app-card flex flex-col items-center justify-center gap-2 p-5">
                    <div class="icon-wrap">
                        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png">
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">Learn</span>
                </a>

                <!-- Auth -->
                <a href="https://auth.stg.life.edu.ph" target="_blank"
                    class="app-card flex flex-col items-center justify-center gap-2 p-5">
                    <div class="icon-wrap">
                        <img src="https://cdn-icons-png.flaticon.com/512/3064/3064155.png">
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">Auth</span>
                </a>

                <!-- Skills AI -->
                <a href="https://skillsai.stg.life.edu.ph" target="_blank"
                    class="app-card flex flex-col items-center justify-center gap-2 p-5">
                    <div class="icon-wrap">
                        <img src="https://cdn-icons-png.flaticon.com/512/4712/4712109.png">
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">Skills AI</span>
                </a>

                <!-- Mentor AI -->
                <a href="https://mentorai.stg.life.edu.ph" target="_blank"
                    class="app-card flex flex-col items-center justify-center gap-2 p-5">
                    <div class="icon-wrap">
                        <img src="https://cdn-icons-png.flaticon.com/512/1995/1995574.png">
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">Mentor AI</span>
                </a>

                <!-- Learn Apps -->
                <a href="https://apps.learn.stg.life.edu.ph" target="_blank"
                    class="app-card flex flex-col items-center justify-center gap-2 p-5">
                    <div class="icon-wrap">
                        <img src="https://cdn-icons-png.flaticon.com/512/1828/1828919.png">
                    </div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">Learn Apps</span>
                </a>

                <!-- Base Manager -->
                <a href="https://base.manager.stg.life.edu.ph" target="_blank"
                    class="app-card flex flex-col items-center justify-center gap-2 p-5">

                    <div class="icon-wrap">
                        <img src="https://cdn-icons-png.flaticon.com/512/1041/1041916.png">
                    </div>

                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">
                        Base Manager
                    </span>
                </a>

                <!-- Studio Learn -->
                <a href="https://studio.learn.stg.life.edu.ph" target="_blank"
                    class="app-card flex flex-col items-center justify-center gap-2 p-5">

                    <div class="icon-wrap">
                        <img src="https://cdn-icons-png.flaticon.com/512/2920/2920244.png">
                    </div>

                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">
                        Studio Learn
                    </span>
                </a>


            </div>

        </div>

    </div>

</body>

</html>
