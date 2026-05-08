{{-- resources/views/visitor/thank-you.blade.php --}}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=crimson-pro:400,600,700&display=swap" rel="stylesheet">

    {{-- Tailwind --}}
    <script src="https://cdn.tailwindcss.com"></script>

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

        .primary-btn:hover {
            box-shadow:
                0 10px 25px rgba(158, 29, 32, 0.18),
                0 0 12px rgba(158, 29, 32, 0.15);
        }
    </style>
</head>

<body class="min-h-screen bg-white flex items-center justify-center p-6">

    {{-- CARD --}}
    <div class="w-full max-w-md bg-[#F2E8DC] border border-[#C9C9C9] rounded-xl overflow-hidden card">

        {{-- TOP ACCENT --}}
        <div class="h-[3px] bg-[#9E1D20]"></div>

        {{-- CONTENT --}}
        <div class="p-8 circuit-bg text-center">

            {{-- LOGO --}}
            <div class="flex justify-center mb-6">

                <img src="https://life.edu.ph/wp-content/uploads/2026/04/LCI-HorizontalStack.png" alt="LCI Logo"
                    class="h-20 object-contain">

            </div>

            {{-- TITLE --}}
            <div class="text-[#9E1D20] serif">

                <div class="text-4xl font-bold tracking-tight leading-tight">
                    Thank You!
                </div>

                <div class="mt-3 text-sm opacity-70 leading-relaxed max-w-sm mx-auto">
                    Your visit has been logged successfully.
                    A member of our team will assist you shortly.
                </div>

            </div>

            {{-- DIVIDER --}}
            <div class="my-8 border-t border-[#C9C9C9]"></div>

            {{-- SUCCESS ICON --}}
            <div class="flex justify-center mb-8">

                <div
                    class="h-24 w-24 rounded-full flex items-center justify-center
               border-[3px] border-[#39FF14]
               bg-[#39FF14]/5">

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-11 w-11 text-[#39FF14]" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>

                </div>

            </div>

            {{-- CTA --}}
            <a href="{{ route('visitor.start') }}"
                class="primary-btn inline-flex items-center justify-center w-full bg-[#9E1D20] hover:bg-[#690F0D] text-white py-3 rounded-md serif text-sm transition duration-200">
                Back to Start
            </a>

        </div>

    </div>

</body>

</html>
