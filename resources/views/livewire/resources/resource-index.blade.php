@php use Illuminate\Support\Facades\Storage; @endphp

<div class="space-y-6" wire:poll.60s x-data x-on:wheel.capture="if ($event.target.matches('input[type=number]')) $event.target.blur()">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-800" role="tablist" aria-label="Resource types">
            <button type="button" role="tab" aria-selected="{{ $activeTab === 'room' ? 'true' : 'false' }}"
                wire:click="showTab('room')"
                class="rounded-md px-4 py-2 text-sm font-medium {{ $activeTab === 'room' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white' }}">
                Rooms ({{ $resources->where('type', 'room')->count() }})
            </button>
            <button type="button" role="tab" aria-selected="{{ $activeTab === 'equipment' ? 'true' : 'false' }}"
                wire:click="showTab('equipment')"
                class="rounded-md px-4 py-2 text-sm font-medium {{ $activeTab === 'equipment' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white' }}">
                Equipment ({{ $resources->where('type', 'equipment')->count() }})
            </button>
        </div>

        <flux:button wire:click="createNew" variant="primary">
            + Add {{ $activeTab === 'room' ? 'Room' : 'Equipment' }}
        </flux:button>

    </div>
    @forelse ($sections as $section)
    <section class="space-y-3">
        <h2 class="text-lg font-semibold">{{ $section['title'] }}</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($section['items'] as $resource)
            <x-ui.card class="p-4">

                <!-- Image -->
                <div class="h-40 w-full mb-3 overflow-hidden rounded-md bg-zinc-200 dark:bg-zinc-700">
                    @if ($resource->image_path)
                        <img src="{{ Storage::disk(config('filesystems.facility_upload_disk'))->url($resource->image_path) }}"
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
                        {{ $resource->floorLabel() }}@if ($resource->location && $resource->location !== $resource->floor) · {{ $resource->location }}@endif • Capacity: {{ $resource->capacity ?? '—' }}
                    </div>
                @endif
                @if ($resource->type === 'equipment')
                    <div class="text-xs text-gray-500 mt-1">Total quantity: {{ $resource->total_quantity }}</div>
                @endif

                @if ($resource->isRoom())
                @php $availability = $availabilityNow[$resource->id]; @endphp
                <div class="mt-2 text-sm font-medium {{ $availability['bookable'] > 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                    @if ($resource->isRoom() && $availability['total'] === 0)
                        Not available: capacity not set
                    @else
                        Available: {{ $availability['bookable'] }}/{{ $availability['total'] }}
                    @endif
                </div>
                @if ($availability['approved'])
                    <p class="text-xs text-zinc-500">Reserved for approved bookings: {{ $availability['approved'] }}</p>
                @endif
                @endif

                <!-- Manage -->
                <div class="mt-3 flex flex-wrap items-center gap-4">

                    <button wire:click="selectResource({{ $resource->id }})"
                        class="text-sm text-blue-600 hover:underline">
                        Manage
                    </button>

                    @if ($resource->isEquipment())
                        <button type="button" wire:click="viewEquipmentBookings({{ $resource->id }})"
                            class="text-sm text-blue-600 hover:underline">View bookings</button>
                    @endif

                </div>

            </x-ui.card>
        @endforeach
        </div>
    </section>
    @empty
        <div class="text-center text-gray-500">No {{ $activeTab === 'room' ? 'rooms' : 'equipment' }} available.</div>
    @endforelse
    <flux:modal name="equipment-bookings" class="w-full md:max-w-3xl">
        @if ($bookingsEquipmentId)
            @php
                $bookedEquipment = $resources->firstWhere('id', $bookingsEquipmentId);
                $equipmentBookings = $this->equipmentBookings;
            @endphp
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $bookedEquipment?->name }} bookings</flux:heading>
                    <p class="text-sm text-zinc-500">Total quantity: {{ $bookedEquipment?->total_quantity }} · All times Asia/Manila</p>
                    <p class="mt-1 text-sm text-zinc-500">Approved quantities are reserved. For Approval quantities are requests awaiting approval.</p>
                </div>
                <p class="text-sm text-zinc-500">Upcoming and ongoing bookings · Earliest event first</p>
                <div class="space-y-5" wire:loading.class="opacity-50">
                    @forelse ($equipmentBookings->take($bookingsLimit)->groupBy(fn ($booking) => $booking->start_datetime->copy()->timezone('Asia/Manila')->format('Y-m')) as $month => $monthlyBookings)
                        <section class="space-y-3">
                            <h2 class="text-lg font-semibold">{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</h2>
                            @foreach ($monthlyBookings->groupBy(fn ($booking) => $booking->start_datetime->copy()->timezone('Asia/Manila')->format('Y-m-d')) as $date => $dailyBookings)
                                <div class="space-y-2">
                                    <h3 class="text-sm font-medium text-zinc-500">{{ \Carbon\Carbon::parse($date)->format('l, F j') }}</h3>
                                    @foreach ($dailyBookings as $booking)
                            <article wire:key="equipment-booking-{{ $booking->id }}" class="rounded-lg border p-4 space-y-2">
                                <h3 class="font-semibold">{{ $booking->title }} <span class="text-xs font-normal text-zinc-500">#{{ $booking->id }}</span></h3>
                                <p class="text-sm">{{ $booking->start_datetime->timezone('Asia/Manila')->format('M j, Y g:i A') }}–{{ $booking->end_datetime->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>
                                <p class="text-sm">Rooms: {{ $booking->room_names }}</p>
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span>Quantity: {{ $booking->equipment->first()?->pivot->quantity }}</span>
                                    <span class="rounded-md px-2 py-1 {{ $booking->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ $booking->status === 'approved' ? 'Approved' : 'For Approval' }}</span>
                                </div>
                                @if ($booking->recurrence_series_id)<p class="text-xs text-zinc-500">Recurring event · {{ $booking->recurrence_position }} of {{ $booking->recurrence_total }} · {{ $booking->recurrence_label }}</p>@endif
                                <details class="text-sm"><summary class="cursor-pointer text-blue-600">View reservation details</summary>
                                    <div class="mt-2 space-y-1">
                                        <p>Name: {{ $booking->requester_name ?: 'Not recorded' }}</p>
                                        <p>{{ $booking->number_of_pax ?? 'Not recorded' }} pax · {{ $booking->setup_arrangement ?: 'Setup not recorded' }}</p>
                                        @if ($booking->notes)<p class="whitespace-pre-line">Notes: {{ $booking->notes }}</p>@endif
                                    </div>
                                </details>
                            </article>
                                    @endforeach
                                </div>
                            @endforeach
                        </section>
                    @empty
                        <p class="py-6 text-center text-zinc-500">No upcoming or ongoing bookings for this equipment.</p>
                    @endforelse
                </div>
                @if ($equipmentBookings->count() > $bookingsLimit)<flux:button wire:click="loadMoreBookings">Load more bookings</flux:button>@endif
                <div class="flex justify-end"><flux:modal.close><flux:button>Close</flux:button></flux:modal.close></div>
            </div>
        @endif
    </flux:modal>
    <flux:modal name="manage-resource-modal" class="md:w-[500px]">

        <div class="space-y-6">

            <!-- Header -->
            <div>
                <flux:heading size="lg">
                    {{ $selectedResourceId ? 'Manage Resource' : 'Add ' . ($type === 'room' ? 'Room' : 'Equipment') }}
                </flux:heading>
                <flux:text class="text-sm text-gray-500">
                    {{ $selectedResourceId ? 'Edit resource details' : 'Enter resource details' }}
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
                                <img src="{{ Storage::disk(config('filesystems.facility_upload_disk'))->url($res->image_path) }}"
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
                    @error('control_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <flux:select wire:model.live="type" label="Type">
                    <option value="room">Room</option>
                    <option value="equipment">Equipment</option>
                </flux:select>

                @if ($type === 'room')
                    <flux:input wire:model="floor" label="Floor" placeholder="e.g. 14th Floor or Ground Floor" />
                    <flux:input wire:model="location" label="Location" />
                    <flux:input wire:model="capacity" type="number" min="1" label="Capacity" />
                @else
                    <flux:input wire:model="total_quantity" type="number" min="1" label="Total quantity" />
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
