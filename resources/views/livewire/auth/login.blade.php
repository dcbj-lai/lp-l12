<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('')" :description="__('')" />
    {{-- Brand Header --}}
    <div class="flex flex-col items-center gap-2 mb-6">
        <img src="{{ asset('images/life-badge.png') }}" alt="LifeSecure Logo"
            class="h-24 w-24 sm:h-14 sm:w-14 object-contain">

        <h1 class="text-2xl sm:text-3xl font-semibold text-zinc-800 dark:text-zinc-100 tracking-tight">
            LifeSecure<span class="align-super text-[10px] sm:text-xs ml-0.5">™</span>
        </h1>

        {{-- Approved Badge --}}
        <span
            class="text-[9px] sm:text-[10px] font-medium px-2.5 py-0.5 rounded-full
                     border border-zinc-300 dark:border-zinc-700
                     text-zinc-600 dark:text-zinc-400
                     bg-zinc-50 dark:bg-zinc-800/40">
            Authentication for LCI Systems
        </span>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        {{-- Google Only Login --}}
        <div class="w-full max-w-sm">
            <a href="{{ route('auth.google') }}"
                class="w-full bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700
                  hover:bg-zinc-50 dark:hover:bg-zinc-800
                  text-zinc-800 dark:text-zinc-200 font-medium py-3 px-4 rounded-xl
                  flex items-center justify-center gap-2 sm:gap-3 shadow-sm transition-all">
                <img src="{{ asset('images/google-logo.svg') }}" class="h-5 w-5" alt="Google Logo">
                <span class="text-sm sm:text-base">Continue with Google</span>
            </a>
        </div>

        {{-- Trust Footer --}}
        <div
            class="flex flex-col items-center gap-0.5 mt-6 text-[10px] sm:text-[11px] text-zinc-500 dark:text-zinc-400">
            <span>Secure access enforced by LifeSecure™</span>
            <span class="text-[9px] sm:text-[10px]">Powered by Google authentication</span>
        </div>

    </form>

    @if (Route::has('register') && env('ALLOW_REGISTRATION'))
        <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Don\'t have an account?') }}
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    @endif
</div>
