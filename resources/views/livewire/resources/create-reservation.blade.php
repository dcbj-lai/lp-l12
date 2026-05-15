<div class="max-w-xl mx-auto space-y-6">

    <!-- Alerts -->
    <x-alert :message="session('success') ?? $errors->first('general')" :type="session('success') ? 'success' : ($errors->has('general') ? 'error' : 'success')" />

    <!-- SECTION: Requester -->
    <div
        class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 space-y-4 shadow-sm">

        <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 border-b pb-2">
            Request Details
        </div>

        <!-- Email -->
        <div class="space-y-1">
            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Your Email</label>
            <input type="email" wire:model="requester_email" placeholder="you@life.edu.ph"
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
            @error('requester_email')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <!-- Title -->
        <div class="space-y-1">
            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Purpose / Title</label>
            <input type="text" wire:model="title"
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
            @error('title')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

    </div>

    <!-- SECTION: Resource -->
    <div
        class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 space-y-4 shadow-sm">

        <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 border-b pb-2">
            Resource Selection
        </div>

        <!-- Room -->
        <div class="space-y-1">
            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Room (optional)</label>
            <select wire:model="resource_id"
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
                <option value="">Select a room</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($resource_id)
            @php
                $selectedRoom = $rooms->firstWhere('id', $resource_id);
            @endphp

            @if ($selectedRoom && $selectedRoom->image_path)
                <div class="mt-2 flex items-center gap-3 p-2 border rounded-md bg-zinc-50 dark:bg-zinc-800">
                    <img src="{{ Storage::disk('s3')->url($selectedRoom->image_path) }}"
                        class="w-14 h-14 object-cover rounded-md border">

                    <div class="text-sm text-zinc-700 dark:text-zinc-200">
                        <div class="font-medium">{{ $selectedRoom->name }}</div>
                        <div class="text-xs text-gray-500">
                            {{ $selectedRoom->location ?? '' }}
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <!-- Equipment -->
        <div class="space-y-2">

            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Equipment</label>

            <!-- Pills Container -->
            <div
                class="min-h-[40px] w-full rounded-md border border-dashed border-zinc-300 dark:border-zinc-600 px-2 py-2 bg-zinc-50 dark:bg-zinc-800 flex flex-wrap gap-2">

                @forelse ($equipment_ids as $id)
                    @php
                        $item = $equipment->firstWhere('id', $id);
                    @endphp

                    @if ($item)
                        <div
                            class="relative group bg-[#9E1D20]/10 text-[#9E1D20] px-2 py-1 flex items-center gap-2 rounded-md text-xs border border-[#9E1D20]/20">

                            <div class="flex items-center gap-2">
                                @if ($item->image_path)
                                    <img src="{{ Storage::disk('s3')->url($item->image_path) }}"
                                        class="w-5 h-5 rounded object-cover border">
                                @endif

                                <span>{{ $item->name }}</span>
                            </div>

                            <!-- Tooltip -->
                            <div
                                class="pointer-events-none absolute left-1/2 bottom-full z-50 mb-2 hidden w-56 -translate-x-1/2 rounded-md bg-zinc-900 px-3 py-2 text-xs text-white shadow-lg group-hover:block">
                                {{ $item->description ?: 'No description available' }}

                                <div
                                    class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-zinc-900">
                                </div>
                            </div>

                            <button type="button" wire:click="removeEquipment({{ $id }})"
                                class="text-red-500 hover:text-red-700 font-bold leading-none">
                                ×
                            </button>

                        </div>
                    @endif
                @empty
                    <span class="text-xs text-gray-400">No equipment selected</span>
                @endforelse

            </div>

            <!-- Selector -->
            <div class="flex gap-2">
                <select wire:model="selected_equipment_to_add"
                    class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">

                    <option value="">Select equipment...</option>

                    @foreach ($equipment as $item)
                        @if (!in_array($item->id, $equipment_ids))
                            <option value="{{ $item->id }}">
                                {{ $item->name }}
                            </option>
                        @endif
                    @endforeach

                </select>

                <button type="button" wire:click="addEquipment"
                    class="px-4 py-2 bg-[#9E1D20] text-white rounded-md text-sm hover:bg-[#690F0D] shadow-sm">
                    Add
                </button>
            </div>

        </div>

    </div>

    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 shadow-sm">

        <!-- Section Header -->
        <div class="mb-3">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                Notes / Instructions
            </h3>
            <p class="text-xs text-gray-500">
                Optional setup details or special requests
            </p>
        </div>

        <!-- Divider -->
        <div class="border-t border-zinc-200 dark:border-zinc-700 mb-3"></div>

        <!-- Textarea -->
        <textarea wire:model="notes" rows="3" placeholder="Setup instructions, special requests, etc."
            class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 
               bg-white dark:bg-zinc-900 
               px-3 py-2 text-sm 
               focus:outline-none focus:ring-2 focus:ring-[#9E1D20]/40 
               focus:border-[#9E1D20]
               transition"></textarea>

        @error('notes')
            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
        @enderror

    </div>

    {{-- Attachment Section --}}
    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 shadow-sm">

        <!-- Header -->
        <div class="mb-3">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                Attachment
            </h3>
            <p class="text-xs text-gray-500">
                Optional supporting file (PDF, image, etc.)
            </p>
        </div>

        <div class="border-t border-zinc-200 dark:border-zinc-700 mb-3"></div>

        <!-- Hidden Input -->
        <input type="file" wire:model="attachment" id="attachment" class="hidden">

        <!-- Custom UI -->
        <label for="attachment"
            class="flex items-center justify-between w-full cursor-pointer
               rounded-md border border-dashed border-zinc-300 dark:border-zinc-600
               px-4 py-3 text-sm
               hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">

            <span class="font-medium text-[#9E1D20]">Click to upload</span>

            <span class="text-xs text-gray-400">
                Max 5MB
            </span>
        </label>

        <!-- Selected file -->
        @if ($attachment)
            <div class="mt-2 text-xs text-green-600">
                Selected: {{ $attachment->getClientOriginalName() }}
            </div>
        @endif

        <!-- Loading -->
        <div wire:loading wire:target="attachment" class="text-xs text-gray-400 mt-1">
            Uploading...
        </div>

        @error('attachment')
            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
        @enderror

    </div>

    <!-- SECTION: Schedule -->
    <div
        class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 space-y-4 shadow-sm">

        <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 border-b pb-2">
            Schedule
        </div>

        <div class="grid grid-cols-2 gap-3">

            <div class="space-y-1">
                <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Start</label>
                <input type="datetime-local" wire:model="start_datetime"
                    class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
                @error('start_datetime')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">End</label>
                <input type="datetime-local" wire:model="end_datetime"
                    class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
                @error('end_datetime')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

        </div>

    </div>

    <!-- Submit -->
    <flux:button wire:click="submitReservation" variant="primary" class="w-full py-3 text-sm font-semibold">
        Submit Reservation
    </flux:button>

</div>
