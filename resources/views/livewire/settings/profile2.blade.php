<div class="space-y-6">

    @include('partials.settings-heading')

    <x-settings.layout heading="Profile" subheading="Update your details">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">

            <!-- FORM -->
            <div class="md:col-span-2 w-full">

                <form wire:submit.prevent="updateProfileInformation" class="space-y-4">

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Name
                        </label>
                        <input type="text" wire:model.defer="name"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm"
                            required>
                        @error('name')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Preferred Name -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Preferred Name
                        </label>
                        <input type="text" wire:model.defer="preferred_name"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Email
                        </label>
                        <input type="email" wire:model.defer="email"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 px-3 py-2 text-sm"
                            readonly>
                    </div>

                    <!-- Work Phone -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Work Phone
                        </label>
                        <input type="text" wire:model.defer="phone_work"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm">
                    </div>

                    <!-- Mobile Phone -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Mobile Phone
                        </label>
                        <input type="text" wire:model.defer="phone_mobile"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm">
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-4 pt-2">
                        <flux:button type="submit" variant="primary" color="emerald">
                            Save
                        </flux:button>
                    </div>

                </form>

            </div>

            <!-- AVATAR -->
            <div class="md:col-span-1 w-full flex flex-col items-center gap-3 md:sticky md:top-6 self-start">

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

                <span class="text-xs text-zinc-500">Click to update</span>

            </div>

        </div>

    </x-settings.layout>

    <!-- AVATAR MODAL -->
    <flux:modal name="avatar-modal" class="md:w-96">

        <form wire:submit.prevent="updateAvatar" class="space-y-6">

            <flux:heading size="lg">Update Profile Photo</flux:heading>

            <div class="flex justify-center">
                @if ($avatar)
                    <img src="{{ $avatar->temporaryUrl() }}" class="h-24 w-24 rounded-full object-cover">
                @endif
            </div>

            <input type="file" wire:model="avatar" class="w-full text-sm">

            @error('avatar')
                <div class="text-red-500 text-sm">{{ $message }}</div>
            @enderror

            <flux:button type="submit" variant="primary" color="teal" class="w-full">
                Upload
            </flux:button>

        </form>

    </flux:modal>

</div>
