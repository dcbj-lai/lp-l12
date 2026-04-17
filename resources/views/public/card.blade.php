<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=crimson-pro:400,600,700&display=swap" rel="stylesheet">

    <title>{{ $user->preferred_name ?: $user->name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .card-glow {
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.10);
        }

        /* subtle crimson grid */
        .circuit-bg {
            background-image:
                linear-gradient(rgba(158, 29, 32, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(158, 29, 32, 0.04) 1px, transparent 1px);
            background-size: 26px 26px;
        }

        .name-font {
            font-family: "Crimson Pro", serif;
            letter-spacing: -0.01em;
        }
    </style>
</head>

<body class="min-h-screen bg-white flex items-center justify-center p-6">

    <!-- CARD -->
    <div
        class="w-full max-w-md 
        bg-[#F2E8DC] 
        border border-[#C9C9C9] 
        rounded-xl 
        overflow-hidden 
        card-glow">

        <!-- Top Accent -->
        <div class="h-1 bg-[#9E1D20]"></div>

        <!-- Content -->
        <div class="p-6 circuit-bg">

            <!-- Avatar + Crest -->
            <div class="flex justify-center mb-4">
                <div class="relative h-24 w-24">

                    <!-- Photo -->
                    <div class="h-24 w-24 rounded-full overflow-hidden border-4 border-white shadow">
                        @if ($user->profile_photo_path)
                            <img src="{{ Storage::disk('s3')->url($user->profile_photo_path) }}"
                                class="h-full w-full object-cover">
                        @else
                            <div
                                class="h-full w-full flex items-center justify-center bg-zinc-200 text-lg font-semibold">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <!-- Crest badge -->
                    <div
                        class="absolute -top-2 -right-2 
    h-10 w-10 rounded-full 
    bg-white border-2 border-[#9E1D20] 
    flex items-center justify-center shadow overflow-hidden">

                        @php
                            $crest = config('app.life_crest_url'); // set this in config or .env
                        @endphp

                        @if ($crest)
                            <img src="{{ $crest }}" alt="Life College Crest" class="h-6 w-6 object-contain"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                            <!-- Fallback -->
                            <span class="hidden text-[10px] font-bold text-[#9E1D20]">
                                LCI
                            </span>
                        @else
                            <span class="text-[10px] font-bold text-[#9E1D20]">
                                LCI
                            </span>
                        @endif

                    </div>
                </div>
            </div>

            <!-- Name -->
            <div class="text-center">
                <div class="text-xl font-bold text-[#9E1D20] name-font">
                    {{ $user->preferred_name ?: $user->name }}
                </div>

                @if ($user->preferred_name)
                    <div class="text-sm text-[#9E1D20] opacity-60 name-font">
                        {{ $user->name }}
                    </div>
                @endif
            </div>

            <!-- Position -->
            <div class="mt-2 text-center text-sm text-[#9E1D20]">
                <span class="{{ $user->position ? '' : 'text-transparent select-none' }}">
                    {{ $user->position ?? '--' }}
                </span>
            </div>

            <!-- Department -->
            <div class="text-center text-sm text-[#9E1D20] opacity-80">
                <span class="{{ $user->department?->name ? '' : 'text-transparent select-none' }}">
                    {{ $user->department->name ?? '--' }}
                </span>
            </div>

            <!-- Divider -->
            <div class="my-4 border-t border-[#C9C9C9]"></div>

            <!-- Contact Info -->
            <div class="space-y-2 text-sm text-[#9E1D20] text-center">

                @if ($user->email)
                    <div>{{ $user->email }}</div>
                @endif

                @if ($user->phone_mobile)
                    <div>{{ $user->phone_mobile }}</div>
                @endif

                @if ($user->phone_work)
                    <div>{{ $user->phone_work }}</div>
                @endif

                @if ($user->address)
                    <div class="mt-2 text-xs leading-relaxed">
                        {{ $user->address }}
                    </div>
                @endif

            </div>

            <!-- Action -->
            <div class="mt-6">
                <a href="{{ route('card.vcard', ['slug' => request()->route('slug')]) }}"
                    class="block w-full text-center 
                    bg-[#9E1D20] hover:bg-[#690F0D]
                    text-white py-2 rounded-md text-sm transition">
                    Save Contact
                </a>
            </div>

            <!-- Footer brand -->
            <div class="mt-4 text-center text-xs text-[#9E1D20] opacity-70">
                Life College International
            </div>

        </div>
    </div>

</body>

</html>
