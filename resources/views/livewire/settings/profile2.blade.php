<div class="space-y-6">

    @include('partials.settings-heading')

    <x-settings.layout heading="Profile" subheading="Update your details">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">

            <!-- FORM -->
            <div class="md:col-span-2 w-full">
                <form wire:submit="updateProfileInformation" class="space-y-4">

                    <flux:input wire:model="name" label="Name" required />
                    <flux:input wire:model="preferred_name" label="Preferred Name" />
                    <flux:input wire:model="email" label="Email" readonly />

                    <flux:input wire:model="phone_work" label="Work Phone" />
                    <flux:input wire:model="phone_mobile" label="Mobile Phone" />

                    <div class="flex items-center gap-4">
                        <flux:button type="submit" variant="primary">
                            Save
                        </flux:button>
                    </div>

                </form>
            </div>

            <!-- AVATAR -->
            <div
                class="md:col-span-1 w-full flex flex-col items-center md:items-center justify-start gap-3 md:sticky md:top-6 self-start">

                <div class="w-full flex flex-col items-center">

                    <flux:modal.trigger name="avatar-modal">
                        <div
                            class="h-24 w-24 rounded-full overflow-hidden bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-primary-500 transition">

                            @if (auth()->user()->profile_photo_path)
                                <img src="{{ Storage::disk('s3')->url(auth()->user()->profile_photo_path) }}"
                                    class="h-full w-full object-cover">
                            @else
                                <span class="text-lg font-semibold">
                                    {{ auth()->user()->initials() }}
                                </span>
                            @endif

                        </div>
                    </flux:modal.trigger>

                    <span class="text-xs text-zinc-500 mt-2">
                        Click to update
                    </span>

                </div>

            </div>

        </div>

    </x-settings.layout>

    <!-- MODAL (keep outside grid) -->
    <flux:modal name="avatar-modal" class="md:w-96">
        <form wire:submit="updateAvatar" class="space-y-6">

            <flux:heading size="lg">Update Profile Photo</flux:heading>

            <div class="flex justify-center">
                @if ($avatar)
                    <img src="{{ $avatar->temporaryUrl() }}" class="h-24 w-24 rounded-full object-cover">
                @endif
            </div>

            <flux:input type="file" wire:model="avatar" label="Choose Photo" />

            @error('avatar')
                <div class="text-red-500 text-sm">{{ $message }}</div>
            @enderror

            <flux:button type="submit" variant="primary">Upload</flux:button>

        </form>
    </flux:modal>

</div>
