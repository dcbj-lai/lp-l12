@php use Illuminate\Support\Facades\Storage; @endphp

<div class="space-y-6">
    <div class="flex justify-end">

        <flux:modal.trigger name="manage-resource-modal">

            <flux:button wire:click="createNew" variant="primary">
                + Add Resource
            </flux:button>

        </flux:modal.trigger>

    </div>
    <!-- GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        @forelse ($resources as $resource)
            <x-ui.card class="p-4">

                <!-- Image -->
                <div class="h-40 w-full mb-3 overflow-hidden rounded-md bg-zinc-200 dark:bg-zinc-700">
                    @if ($resource->image_path)
                        <img src="{{ Storage::disk('s3')->url($resource->image_path) }}"
                            class="h-full w-full object-cover">
                    @else
                        <div class="flex items-center justify-center h-full text-sm text-gray-500">
                            No Image
                        </div>
                    @endif
                </div>

                <!-- Name -->
                <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
                    {{ $resource->name }}
                </h2>

                <!-- Type -->
                <p class="text-sm text-gray-500 capitalize">
                    {{ $resource->type }}
                </p>

                <!-- Meta -->
                @if ($resource->type === 'room')
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $resource->location ?? '—' }} • Capacity: {{ $resource->capacity ?? '—' }}
                    </div>
                @endif

                <!-- Manage -->
                <div class="mt-3">

                    <flux:modal.trigger name="manage-resource-modal">
                        <button wire:click="selectResource({{ $resource->id }})"
                            class="text-sm text-blue-600 hover:underline">
                            Manage
                        </button>
                    </flux:modal.trigger>

                </div>

            </x-ui.card>
        @empty
            <div class="col-span-full text-center text-gray-500">
                No resources available.
            </div>
        @endforelse

    </div>
    <flux:modal name="manage-resource-modal" class="md:w-[500px]">

        <div class="space-y-6">

            <!-- Header -->
            <div>
                <flux:heading size="lg">
                    {{ $selectedResourceId ? 'Manage Resource' : 'Add Resource' }}
                </flux:heading>
                <flux:text class="text-sm text-gray-500">
                    Edit resource details
                </flux:text>
            </div>
            <div class="flex flex-col items-center gap-3">

                <flux:tooltip content="Change resource image">

                    <flux:modal.trigger name="resource-image-preview">

                        <div
                            class="h-32 w-32 rounded-lg overflow-hidden
                bg-zinc-200 dark:bg-zinc-700
                flex items-center justify-center
                cursor-pointer
                hover:ring-2 hover:ring-primary-500 transition">

                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}" class="h-full w-full object-cover">
                            @elseif ($selectedResourceId && ($res = $resources->firstWhere('id', $selectedResourceId)) && $res->image_path)
                                <img src="{{ Storage::disk('s3')->url($res->image_path) }}"
                                    class="h-full w-full object-cover">
                            @else
                                <span class="text-sm text-gray-500">
                                    No Image
                                </span>
                            @endif

                        </div>

                    </flux:modal.trigger>

                </flux:tooltip>

                <!-- Upload -->
                <flux:input type="file" wire:model="image" label="Upload Image" />

                @error('image')
                    <div class="text-red-500 text-sm">{{ $message }}</div>
                @enderror

            </div>
            <!-- Form -->
            <div class="space-y-4">

                <flux:input wire:model="name" label="Name" />
                <div>
                    <label class="block text-sm mb-1">Control Number</label>
                    <input type="text" wire:model="control_number" placeholder="e.g. RM-101"
                        class="w-full rounded-md border px-3 py-2 text-sm">
                </div>

                <flux:select wire:model="type" label="Type">
                    <option value="room">Room</option>
                    <option value="equipment">Equipment</option>
                </flux:select>

                @if ($type === 'room')
                    <flux:input wire:model="location" label="Location" />
                    <flux:input wire:model="capacity" type="number" label="Capacity" />
                @endif

                <flux:textarea wire:model="description" label="Description" />

            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">

                @if ($selectedResourceId)
                    <!-- DELETE (only in edit mode) -->
                    <flux:modal.trigger name="confirm-delete-resource">
                        <button class="text-sm text-red-600 hover:text-red-800 font-medium">
                            Delete Resource
                        </button>
                    </flux:modal.trigger>
                @else
                    <div></div>
                @endif

                <!-- SAVE / CREATE -->
                @if ($selectedResourceId)
                    <flux:button wire:click="updateSelected" variant="primary">
                        Save
                    </flux:button>
                @else
                    <flux:button wire:click="store" variant="primary">
                        Create
                    </flux:button>
                @endif

            </div>

        </div>

    </flux:modal>
    <flux:modal name="confirm-delete-resource" class="md:w-[400px]">

        <div class="space-y-6">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Delete Resource</flux:heading>
                <flux:text class="text-sm text-gray-500">
                    This action cannot be undone.
                </flux:text>
            </div>

            <!-- Warning -->
            <div class="text-sm text-zinc-700 dark:text-zinc-300">
                Are you sure you want to delete this resource?
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2">

                <!-- Cancel -->
                <flux:modal.close>
                    <flux:button variant="ghost">
                        Cancel
                    </flux:button>
                </flux:modal.close>

                <!-- Confirm Delete -->
                <flux:button wire:click="deleteSelected" variant="danger">
                    Delete
                </flux:button>

            </div>

        </div>

    </flux:modal>

</div>
