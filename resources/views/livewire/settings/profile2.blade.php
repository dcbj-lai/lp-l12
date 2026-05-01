<div class="space-y-6">

    @include('partials.settings-heading')

    <x-settings.layout heading="Profile" subheading="Update your details">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">

            <!-- FORM -->
            <div class="md:col-span-2 w-full" wire:key="profile-form">

                <form wire:submit.prevent="updateProfileInformation" class="space-y-4">

                    <flux:input wire:model.live="name" label="Name" required />

                    <flux:input wire:model.live="preferred_name" label="Preferred Name" />

                    <flux:input wire:model.live="email" label="Email" readonly />

                    <flux:input wire:model.live="phone_work" label="Work Phone" />

                    <flux:input wire:model.live="phone_mobile" label="Mobile Phone" />

                    <div class="flex items-center gap-4 pt-2">

                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Save</span>
                            <span wire:loading>Saving...</span>
                        </flux:button>

                    </div>

                </form>

            </div>

            <!-- AVATAR -->
            <div class="md:col-span-1 w-full flex flex-col items-center justify-start gap-3 md:sticky md:top-6 self-start"
                wire:key="profile-avatar">

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

    <!-- AVATAR MODAL -->
    <flux:modal name="avatar-modal" class="md:w-96" wire:key="avatar-modal">

        <form wire:submit.prevent="updateAvatar" class="space-y-6">

            <flux:heading size="lg">
                Update Profile Photo
            </flux:heading>

            <div class="flex justify-center">

                @if ($avatar)
                    <img src="{{ $avatar->temporaryUrl() }}" class="h-24 w-24 rounded-full object-cover">
                @endif

            </div>

            <flux:input type="file" wire:model="avatar" label="Choose Photo" />

            @error('avatar')
                <div class="text-red-500 text-sm">
                    {{ $message }}
                </div>
            @enderror

            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Upload</span>
                <span wire:loading>Uploading...</span>
            </flux:button>

        </form>

    </flux:modal>

</div>
