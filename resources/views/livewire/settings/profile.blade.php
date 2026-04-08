<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $preferred_name = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->preferred_name = Auth::user()->preferred_name ?? '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],

            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')
    <div class="flex justify-end">
        <div
            class="flex items-center gap-3 bg-zinc-50 dark:bg-zinc-900 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-700">

            {{-- Avatar --}}
            <div
                class="h-12 w-12 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                @if (auth()->user()->profile_photo_path)
                    <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}" class="h-full w-full object-cover">
                @else
                    <span class="text-sm font-semibold text-black dark:text-white">
                        {{ auth()->user()->initials() }}
                    </span>
                @endif
            </div>

            {{-- Name + Preferred Name --}}
            <div class="leading-tight">
                <div class="font-semibold text-sm">
                    {{ auth()->user()->name }}
                </div>

                <div class="text-[11px] text-yellow-300 drop-shadow-[0_0_6px_rgba(253,224,71,0.9)]">
                    {{ auth()->user()->preferred_name ?? '' }}
                </div>
            </div>

        </div>
    </div>
    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus
                autocomplete="name" />
            <flux:input wire:model="preferred_name" :label="__('Preferred Name')" type="text"
                placeholder="Nickname / display name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email"
                    readonly />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer"
                                wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        {{-- <livewire:settings.delete-user-form /> --}}
    </x-settings.layout>
</section>
