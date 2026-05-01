<div class="space-y-6">

    @include('partials.settings-heading')

    <x-settings.layout heading="Password" subheading="Update your password">

        <form wire:submit="updatePassword">

            <!-- SECTION CONTAINER -->
            <div class="max-w-2xl space-y-6">

                <div class="space-y-4">

                    <flux:input wire:model="current_password" label="Current Password" type="password" required />

                    <flux:input wire:model="password" label="New Password" type="password" required />

                    <flux:input wire:model="password_confirmation" label="Confirm Password" type="password" required />

                    <div class="flex items-center gap-4 pt-2">
                        <flux:button type="submit" variant="primary">
                            Update Password
                        </flux:button>
                    </div>

                </div>

            </div>

        </form>

    </x-settings.layout>

</div>
