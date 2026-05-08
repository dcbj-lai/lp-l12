{{-- resources/views/visitor/form.blade.php --}}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Form</title>

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
                    Visitor Form
                </div>

                <div class="mt-2 text-sm opacity-70">
                    Complete your visitor information
                </div>

            </div>

            {{-- DIVIDER --}}
            <div class="my-6 border-t border-[#C9C9C9]"></div>

            {{-- FORM --}}
            <form method="POST" action="{{ route('visitor.form.submit', $visitor->id) }}" class="space-y-5">
                @csrf

                {{-- FULL NAME --}}
                <div>

                    <label class="block mb-2 text-sm serif text-[#9E1D20]">
                        Full Name
                    </label>

                    <input type="text" name="full_name" required value="{{ old('full_name', $visitor->full_name) }}"
                        class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 outline-none transition focus:border-[#9E1D20] focus:ring-4 focus:ring-[#9E1D20]/10">

                    @error('full_name')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- COMPANY --}}
                <div>

                    <label class="block mb-2 text-sm serif text-[#9E1D20]">
                        Company
                    </label>

                    <input type="text" name="company" required value="{{ old('company', $visitor->company) }}"
                        class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 outline-none transition focus:border-[#9E1D20] focus:ring-4 focus:ring-[#9E1D20]/10">

                    @error('company')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- ADDRESS --}}
                <div>

                    <label class="block mb-2 text-sm serif text-[#9E1D20]">
                        Address
                    </label>

                    <textarea name="address" required rows="3"
                        class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 outline-none transition resize-none focus:border-[#9E1D20] focus:ring-4 focus:ring-[#9E1D20]/10">{{ old('address', $visitor->address) }}</textarea>

                    @error('address')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- MOBILE --}}
                <div>

                    <label class="block mb-2 text-sm serif text-[#9E1D20]">
                        Mobile Number
                    </label>

                    <input type="text" name="mobile" required value="{{ old('mobile', $visitor->mobile) }}"
                        class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 outline-none transition focus:border-[#9E1D20] focus:ring-4 focus:ring-[#9E1D20]/10">

                    @error('mobile')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- PERSON TO VISIT --}}
                <div>

                    <label class="block mb-2 text-sm serif text-[#9E1D20]">
                        Person to Visit
                    </label>

                    <select name="visited_user_id" required
                        class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 outline-none transition focus:border-[#9E1D20] focus:ring-4 focus:ring-[#9E1D20]/10">

                        <option value="">
                            -- Select --
                        </option>

                        @foreach ($users as $id => $name)
                            <option value="{{ $id }}"
                                {{ old('visited_user_id', $visitor->visited_user_id) == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach

                    </select>

                    @error('visited_user_id')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- PURPOSE --}}
                <div>

                    <label class="block mb-2 text-sm serif text-[#9E1D20]">
                        Purpose
                    </label>

                    <input type="text" name="purpose" required value="{{ old('purpose', $visitor->purpose) }}"
                        class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 outline-none transition focus:border-[#9E1D20] focus:ring-4 focus:ring-[#9E1D20]/10">

                    @error('purpose')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- SUBMIT --}}
                <button type="submit"
                    class="primary-btn w-full bg-[#9E1D20] hover:bg-[#690F0D] text-white py-3 rounded-md serif text-sm transition duration-200">
                    Submit
                </button>

            </form>

            {{-- PRIVACY POLICY --}}
            <div class="mt-6 text-center">

                <a href="https://laicollege.edu.ph/privacy-policy/" target="_blank"
                    class="text-xs text-[#9E1D20] opacity-70 hover:opacity-100 hover:underline transition">
                    Privacy Policy
                </a>

            </div>

        </div>

    </div>

</body>

</html>
