<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $user->preferred_name ?: $user->name }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=crimson-pro:400,600,700&display=swap" rel="stylesheet">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .serif {
            font-family: "Crimson Pro", serif;
        }

        /* subtle grid */
        .circuit-bg {
            background-image:
                linear-gradient(rgba(158, 29, 32, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(158, 29, 32, 0.04) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .card {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .card-stage {
            perspective: 1400px;
            touch-action: pan-y;
            user-select: none;
        }

        .flip-card {
            cursor: grab;
            display: grid;
            transform-style: preserve-3d;
            transition: transform 0.6s ease;
            will-change: transform;
        }

        .flip-card.is-flipped {
            transform: rotateY(180deg);
        }

        .flip-card.is-dragging {
            cursor: grabbing;
            transition: none;
        }

        .card-face {
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            grid-area: 1 / 1;
        }

        .card-back {
            transform: rotateY(180deg);
        }

        @media (prefers-reduced-motion: reduce) {
            .flip-card {
                transition: none;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-white flex items-center justify-center p-6">

    <div class="w-full max-w-md">
        <div class="card-stage">
            <div id="business-card" class="flip-card">
                <section class="card-face bg-[#F2E8DC] border border-[#C9C9C9] rounded-xl overflow-hidden card">

                    <!-- top accent -->
                    <div class="h-[3px] bg-[#9E1D20]"></div>

                    <!-- content -->
                    <div class="p-8 circuit-bg">

                        <!-- Avatar -->
                        <div class="flex justify-center mb-6">
                            <div class="h-28 w-28 rounded-full overflow-hidden border-4 border-white shadow">
                                @if ($user->profile_photo_path)
                                    <img src="{{ Storage::disk('s3')->url($user->profile_photo_path) }}"
                                        class="h-full w-full object-cover">
                                @else
                                    <div
                                        class="h-full w-full flex items-center justify-center bg-zinc-200 text-xl font-semibold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Name -->
                        <div class="text-center text-[#9E1D20] serif">
                            <div class="text-2xl font-bold leading-tight tracking-tight">
                                {{ $user->preferred_name ?: $user->name }}
                            </div>

                            @if ($user->preferred_name)
                                <div class="text-sm opacity-70 leading-tight">
                                    {{ $user->name }}
                                </div>
                            @endif
                        </div>

                        <!-- Role -->
                        <div class="mt-3 text-center text-[#9E1D20] serif text-sm">
                            <div class="leading-tight">
                                {{ $user->position ?? '' }}
                            </div>

                            <div class="opacity-70 leading-tight">
                                {{ $user->department->name ?? '' }}
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="my-6 border-t border-[#C9C9C9]"></div>

                        <!-- Contact -->
                        <div class="text-center text-[#9E1D20] serif text-sm space-y-1">

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

                        <!-- CTA -->
                        <div class="mt-8">
                            <a href="{{ route('card.vcard', ['slug' => request()->route('slug')]) }}"
                                class="block w-full text-center bg-[#9E1D20] hover:bg-[#690F0D] text-white py-2 rounded-md serif text-sm transition">
                                Save Contact
                            </a>
                        </div>

                        <!-- Address + Logo (aligned block) -->
                        <div class="mt-8 flex items-end justify-between">

                            <!-- Address -->
                            <div class="text-[#9E1D20] serif text-xs leading-relaxed">
                                LAI College, Ortigas East, Ortigas Ave.<br>
                                cor. C-5 Road, Ugong, Pasig City,<br>
                                Metro Manila, Philippines
                            </div>

                            <!-- Logo -->
                            <div class="flex items-end">

                                @php
                                    $crest = config('app.life_crest_url');
                                @endphp

                                @if ($crest)
                                    <img src="{{ $crest }}" class="h-10 object-contain mix-blend-multiply">
                                @else
                                    <!-- Placeholder -->
                                    <div
                                        class="h-10 w-20 border border-[#9E1D20] flex items-center justify-center text-[10px] text-[#9E1D20]">
                                        LOGO
                                    </div>
                                @endif

                            </div>

                        </div>

                    </div>
                </section>

                <section
                    class="card-face card-back bg-[#9E1D20] border border-[#690F0D] rounded-xl overflow-hidden card text-white">
                    <div class="min-h-full p-8 flex flex-col items-center justify-between text-center">
                        <div class="serif">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-[#F2E8DC]">
                                Life College International
                            </div>
                            <div class="mt-2 text-2xl font-bold leading-tight">
                                {{ $user->preferred_name ?: $user->name }}
                            </div>
                            @if ($user->position)
                                <div class="mt-1 text-sm text-[#F2E8DC]">
                                    {{ $user->position }}
                                </div>
                            @endif
                        </div>

                        <div class="my-8 rounded-xl bg-white p-4 shadow-lg">
                            <img src="data:image/svg+xml;base64,{{ $qrImage }}" alt="QR code for {{ $cardUrl }}"
                                class="h-52 w-52">
                        </div>

                        <div class="w-full">
                            <div class="mx-auto max-w-xs break-all rounded-md border border-white/30 px-3 py-2 text-xs text-[#F2E8DC]">
                                {{ $cardUrl }}
                            </div>
                            <div class="mt-5 serif text-sm font-semibold text-[#F2E8DC]">
                                Learn and Live Fully.
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const card = document.getElementById('business-card');
            let isFlipped = false;
            let startX = 0;
            let startY = 0;
            let currentX = 0;
            let activePointerId = null;
            let isDragging = false;
            let hasHorizontalIntent = false;

            const setFlipped = (flipped) => {
                isFlipped = flipped;
                card.style.transform = '';
                card.classList.toggle('is-flipped', flipped);
            };

            const dragRotation = (deltaX) => {
                const progress = Math.max(-1, Math.min(1, deltaX / 220));
                const base = isFlipped ? 180 : 0;
                return base - (progress * 70);
            };

            card.addEventListener('pointerdown', (event) => {
                if (event.button !== undefined && event.button !== 0) {
                    return;
                }

                activePointerId = event.pointerId;
                startX = event.clientX;
                startY = event.clientY;
                currentX = event.clientX;
                isDragging = true;
                hasHorizontalIntent = false;
                card.setPointerCapture(activePointerId);
                card.classList.add('is-dragging');
            }, {
                passive: true
            });

            card.addEventListener('pointermove', (event) => {
                if (!isDragging || event.pointerId !== activePointerId) {
                    return;
                }

                currentX = event.clientX;
                const deltaX = currentX - startX;
                const deltaY = event.clientY - startY;

                if (!hasHorizontalIntent && Math.abs(deltaX) > 12 && Math.abs(deltaX) > Math.abs(deltaY) * 1.35) {
                    hasHorizontalIntent = true;
                }

                if (!hasHorizontalIntent) {
                    return;
                }

                event.preventDefault();
                card.style.transform = `rotateY(${dragRotation(deltaX)}deg)`;
            });

            const finishDrag = (event) => {
                if (!isDragging || event.pointerId !== activePointerId) {
                    return;
                }

                const deltaX = currentX - startX;
                const shouldFlip = hasHorizontalIntent && Math.abs(deltaX) >= 80;

                card.classList.remove('is-dragging');

                if (card.hasPointerCapture(activePointerId)) {
                    card.releasePointerCapture(activePointerId);
                }

                isDragging = false;
                activePointerId = null;

                if (shouldFlip) {
                    setFlipped(deltaX < 0 ? true : false);
                    return;
                }

                setFlipped(isFlipped);
            };

            card.addEventListener('pointerup', finishDrag);
            card.addEventListener('pointercancel', finishDrag);
            card.addEventListener('lostpointercapture', () => {
                if (!isDragging) {
                    return;
                }

                isDragging = false;
                activePointerId = null;
                card.classList.remove('is-dragging');
                setFlipped(isFlipped);
            });
        })();
    </script>
</body>

</html>
