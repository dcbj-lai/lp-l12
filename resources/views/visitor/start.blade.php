{{-- resources/views/start.blade.php --}}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Check-In</title>

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
        <div class="p-8 circuit-bg">

            {{-- LOGO --}}
            <div class="flex justify-center mb-6">

                <img src="https://life.edu.ph/wp-content/uploads/2026/04/LCI-HorizontalStack.png" alt="LCI Logo"
                    class="h-20 object-contain">

            </div>

            {{-- TITLE --}}
            <div class="text-center text-[#9E1D20] serif">

                <div class="text-3xl font-bold tracking-tight leading-tight">
                    Visitor Check-In
                </div>

                <div class="mt-2 text-sm opacity-70">
                    Secure visitor authentication portal
                </div>

            </div>

            {{-- DIVIDER --}}
            <div class="my-6 border-t border-[#C9C9C9]"></div>

            {{-- Step 1: Enter email --}}
            @if (!session('step'))
                <form method="POST" action="{{ route('visitor.sendOtp') }}">

                    @csrf

                    <div class="mb-5">

                        <label for="email" class="block mb-2 text-sm serif text-[#9E1D20]">
                            Email Address
                        </label>

                        <input id="email" type="email" name="email" required value="{{ old('email') }}"
                            placeholder="you@example.com"
                            class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 outline-none transition focus:border-[#9E1D20] focus:ring-4 focus:ring-[#9E1D20]/10">

                        @error('email')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <button type="submit"
                        class="primary-btn w-full bg-[#9E1D20] hover:bg-[#690F0D] text-white py-3 rounded-md serif text-sm transition duration-200">
                        Start
                    </button>

                </form>
            @endif

            {{-- Step 2: Enter OTP --}}
            @if (session('step') === 'verify')
                <form method="POST" action="{{ route('visitor.verifyOtp') }}">

                    @csrf

                    <input type="hidden" name="email" value="{{ session('email') }}">

                    <div class="mb-5">

                        <label for="otp" class="block mb-2 text-sm serif text-[#9E1D20]">
                            Enter OTP (sent to {{ session('email') }})
                        </label>

                        <input id="otp" type="text" name="otp" maxlength="6" required
                            class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-center tracking-[0.35em] text-lg text-zinc-800 outline-none transition focus:border-[#9E1D20] focus:ring-4 focus:ring-[#9E1D20]/10">

                        @error('otp')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <button type="submit"
                        class="primary-btn w-full bg-[#9E1D20] hover:bg-[#690F0D] text-white py-3 rounded-md serif text-sm transition duration-200">
                        Go
                    </button>

                </form>
            @endif

        </div>

    </div>

</body>

</html>
