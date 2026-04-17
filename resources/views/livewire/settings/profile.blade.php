// profile.blade.php
<?php
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $preferred_name = '';

    public $avatar; // temporary uploaded file

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->preferred_name = Auth::user()->preferred_name ?? '';
    }

    public function updateAvatar(): void
    {
        $user = Auth::user();

        $this->validate([
            'avatar' => ['required', 'image', 'max:2048'], // 2MB
        ]);

        // Optional: delete old avatar
        if ($user->profile_photo_path) {
            Storage::disk('s3')->delete($user->profile_photo_path);
        }

        // Generate filename
        $filename = 'avatar_' . time() . '.' . $this->avatar->getClientOriginalExtension();

        $path = $this->avatar->storeAs('avatars/' . $user->id, $filename, 's3');

        Storage::disk('s3')->setVisibility($path, 'public');

        $user->update([
            'profile_photo_path' => $path,
        ]);

        $this->modal('avatar-modal')->close();

        // reset input
        $this->reset('avatar');

        $this->dispatch('profile-updated');
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
        <div class="flex flex-col items-center gap-2">

            <flux:tooltip content="Change avatar">

                {{-- Tooltip anchor MUST be a plain element --}}
                <div class="inline-block">

                    <flux:modal.trigger name="avatar-modal">

                        <div
                            class="h-24 w-24 rounded-full overflow-hidden
                            bg-zinc-200 dark:bg-zinc-700
                            flex items-center justify-center
                            cursor-pointer
                            hover:ring-2 hover:ring-primary-500 transition">

                            @if (auth()->user()->profile_photo_path)
                                <img src="{{ Storage::disk('s3')->url(auth()->user()->profile_photo_path) }}"
                                    class="h-full w-full object-cover">
                            @else
                                <span class="text-lg font-semibold text-black dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            @endif

                        </div>

                    </flux:modal.trigger>

                </div>

            </flux:tooltip>

            {{-- Preferred Name --}}
            @if (auth()->user()->preferred_name)
                <div
                    class="text-xs font-semibold text-yellow-300
                    drop-shadow-[0_0_6px_rgba(253,224,71,0.9)]">
                    {{ auth()->user()->preferred_name }}
                </div>
            @endif

        </div>
        <flux:modal name="avatar-modal" class="md:w-96">
            <form wire:submit="updateAvatar" class="space-y-6">

                <div>
                    <flux:heading size="lg">Update Profile Photo</flux:heading>
                    <flux:text class="mt-2">
                        Upload a new avatar image.
                    </flux:text>
                </div>

                {{-- Preview --}}
                <div class="flex justify-center">
                    @if ($avatar)
                        <img src="{{ $avatar->temporaryUrl() }}" class="h-24 w-24 rounded-full object-cover">
                    @elseif(auth()->user()->profile_photo_path)
                        <img src="{{ Storage::disk('s3')->url(auth()->user()->profile_photo_path) }}"
                            class="h-24 w-24 rounded-full object-cover">
                    @else
                        <div class="h-24 w-24 rounded-full bg-zinc-200 flex items-center justify-center">
                            <span class="text-sm font-semibold">
                                {{ auth()->user()->initials() }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- File Input --}}
                <flux:input type="file" wire:model="avatar" label="Choose Photo" />

                @error('avatar')
                    <div class="text-red-500 text-sm">{{ $message }}</div>
                @enderror

                {{-- Actions --}}
                <div class="flex">
                    <flux:spacer />

                    <flux:button type="submit" variant="primary">
                        Upload
                    </flux:button>
                </div>

            </form>
        </flux:modal>
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
