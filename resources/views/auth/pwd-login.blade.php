<x-layouts.auth>
    <div class="max-w-sm mx-auto flex flex-col gap-6">

        <form method="POST" action="{{ route('pwd.login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input name="email" label="Email" type="email" required autofocus />

            <flux:input name="password" label="Password" type="password" required />

            <flux:button variant="primary" type="submit" class="w-full" color="teal">
                Login
            </flux:button>

            @if ($errors->any())
                <div class="text-red-600 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif
        </form>

    </div>
</x-layouts.auth>
