<div class="max-w-xl mx-auto space-y-6" x-data x-on:wheel.capture="if ($event.target.matches('input[type=number]')) $event.target.blur()">

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
            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Event Name *</label>
            <input type="text" wire:model="title"
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
            @error('title')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div><label class="text-xs font-medium">Number of pax *</label><input type="number" min="1" wire:model="number_of_pax" class="w-full rounded-md border px-3 py-2 text-sm">@error('number_of_pax') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror</div>
            <div><label class="text-xs font-medium">Contact number *</label><input type="tel" wire:model="contact_number" class="w-full rounded-md border px-3 py-2 text-sm">@error('contact_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror</div>
        </div>
        <div>
            <label class="text-xs font-medium">Setup arrangement *</label>
            <select wire:model.change="setup_arrangement"
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
                <option value="">Select an arrangement</option>
                <option value="Workshop">Workshop</option>
                <option value="Theater">Theater</option>
                <option value="Classroom">Classroom</option>
                <option value="Boardroom">Boardroom</option>
                <option value="External (c/o organizer)">External (c/o organizer)</option>
            </select>
            @error('setup_arrangement') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Room *</label>
            <select wire:model.change="resource_id"
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
                <option value="">Select a room</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                @endforeach
            </select>
        </div>
        @error('resource_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        @if ($resource_id)
            @php
                $selectedRoom = $rooms->firstWhere('id', $resource_id);
            @endphp

            @if ($selectedRoom && $selectedRoom->image_path)
                <div class="mt-2 flex items-center gap-3 p-2 border rounded-md bg-zinc-50 dark:bg-zinc-800">
                    <img src="{{ Storage::disk(config('filesystems.facility_upload_disk'))->url($selectedRoom->image_path) }}"
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

                @forelse ($equipment_quantities as $id => $quantity)
                    @php
                        $item = $equipment->firstWhere('id', $id);
                    @endphp

                    @if ($item)
                            <div
                                class="relative group bg-[#9E1D20]/10 text-[#9E1D20] px-2 py-1 flex items-center gap-2 rounded-md text-xs border border-[#9E1D20]/20">

                                <div class="flex items-center gap-2">
                                    @if ($item->image_path)
                                        <img src="{{ Storage::disk(config('filesystems.facility_upload_disk'))->url($item->image_path) }}"
                                            class="w-5 h-5 rounded object-cover border">
                                    @endif

                                    <span>{{ $item->name }} × {{ $quantity }}</span>
                                </div>

                                <div
                                    class="pointer-events-none absolute left-1/2 bottom-full z-[9999] mb-2 hidden w-max max-w-[260px] -translate-x-1/2 whitespace-normal rounded-md bg-zinc-900 px-3 py-2 text-xs leading-relaxed text-white shadow-lg group-hover:block">

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
                <select wire:model.live="selected_equipment_to_add"
                    class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">

                    <option value="">Select equipment...</option>

                    @foreach ($equipment as $item)
                        @if (!array_key_exists($item->id, $equipment_quantities))
                            <option value="{{ $item->id }}">
                                {{ $item->name }}
                            </option>
                        @endif
                    @endforeach

                </select>

                @php $availableEquipmentQuantity = $this->selectedEquipmentAvailableQuantity(app(\App\Services\ResourceReservationService::class)); @endphp
                <select wire:model="selected_equipment_quantity" aria-label="Equipment quantity" class="w-24 rounded-md border px-2 py-2 text-sm" @disabled(!$selected_equipment_to_add || $availableEquipmentQuantity < 1)>
                    @if (!$selected_equipment_to_add)
                        <option value="1">Quantity</option>
                    @elseif ($availableEquipmentQuantity < 1)
                        <option value="0">0 available</option>
                    @else
                        @for ($quantity = 1; $quantity <= $availableEquipmentQuantity; $quantity++)
                            <option value="{{ $quantity }}">{{ $quantity }}</option>
                        @endfor
                    @endif
                </select>

                <button type="button" wire:click="addEquipment"
                    @disabled(!$selected_equipment_to_add || $availableEquipmentQuantity < 1)
                    class="px-4 py-2 bg-[#9E1D20] text-white rounded-md text-sm hover:bg-[#690F0D] shadow-sm">
                    Add
                </button>
            </div>
            @error('equipment_quantities.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

        </div>

    </div>

    <div class="rounded-lg border bg-white dark:bg-zinc-900 p-4 space-y-2">
        <label class="text-sm font-semibold">Disposables and consumables</label>
        <p class="text-xs text-amber-700">These are to be provided by or charged to the organizer.</p>
        <textarea wire:model="consumables" rows="2" class="w-full rounded-md border px-3 py-2 text-sm" placeholder="List any disposables or consumables needed"></textarea>
        @error('consumables') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                Floor Plan *
            </h3>
            <p class="text-xs text-gray-500">
                Required PDF or image, maximum 10 MB
            </p>
        </div>

        <div class="border-t border-zinc-200 dark:border-zinc-700 mb-3"></div>

        <!-- Hidden Input -->
        <input type="file" wire:model="floor_plan" id="floor_plan" class="hidden" accept=".pdf,.jpg,.jpeg,.png">

        <!-- Custom UI -->
        <label for="floor_plan"
            class="flex items-center justify-between w-full cursor-pointer
               rounded-md border border-dashed border-zinc-300 dark:border-zinc-600
               px-4 py-3 text-sm
               hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">

            <span class="font-medium text-[#9E1D20]">Click to upload</span>

            <span class="text-xs text-gray-400">
                Max 10MB
            </span>
        </label>

        <!-- Selected file -->
        @if ($floor_plan)
            <div class="mt-2 text-xs text-green-600">
                Selected: {{ $floor_plan->getClientOriginalName() }}
            </div>
        @endif

        <!-- Loading -->
        <div wire:loading wire:target="floor_plan" class="text-xs text-gray-400 mt-1">
            Uploading...
        </div>

        @error('floor_plan')
            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
        @enderror

    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-3">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                Gate Pass <span class="font-normal text-zinc-500">(optional)</span>
            </h3>
            <p class="text-xs text-gray-500">
                Upload a completed gate pass if needed. PDF, Word, or image, maximum 10 MB.
            </p>
        </div>

        <div class="mb-3 border-t border-zinc-200 dark:border-zinc-700"></div>

        <input type="file" wire:model="gate_pass" id="gate_pass" class="hidden"
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">

        <label for="gate_pass"
            class="flex w-full cursor-pointer items-center justify-between rounded-md border border-dashed border-zinc-300 px-4 py-3 text-sm transition hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-700">
            <span class="font-medium text-[#9E1D20]">Click to upload</span>
            <span class="text-xs text-gray-400">Max 10 MB</span>
        </label>

        @if ($gate_pass)
            <div class="mt-2 text-xs text-green-600">
                Selected: {{ $gate_pass->getClientOriginalName() }}
            </div>
        @endif

        <div wire:loading wire:target="gate_pass" class="mt-1 text-xs text-gray-400">
            Uploading...
        </div>

        @error('gate_pass')
            <span class="mt-1 block text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- SECTION: Schedule -->
    <div
        class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 space-y-4 shadow-sm">

        <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 border-b pb-2">
            Schedule
        </div>

        <div class="space-y-1">
            <label for="event_date" class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Event Date *</label>
            <input id="event_date" type="date" wire:model.live="event_date"
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-2 text-sm">
            @error('event_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

            <div class="space-y-1">
                <label for="start_time" class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Start Time *</label>
                <input id="start_time" type="time" wire:model.change="start_time"
                    class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
                @error('start_time')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="end_time" class="text-xs font-medium text-zinc-600 dark:text-zinc-400">End Time *</label>
                <input id="end_time" type="time" wire:model.change="end_time"
                    class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-2 text-sm focus:ring-2 focus:ring-[#9E1D20]/20 focus:border-[#9E1D20]">
                @error('end_time')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

        </div>

    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-4 space-y-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 border-b pb-2">Recurring Event <span class="font-normal text-zinc-500">(optional)</span></div>
        <p class="text-xs text-zinc-500">The event date above is the first booking. Each repeat uses the same room, equipment, and time.</p>
        <div class="space-y-1">
                <label for="recurrence" class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Repeat</label>
                <select id="recurrence" wire:model.live="recurrence" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-[#9E1D20] focus:ring-2 focus:ring-[#9E1D20]/20 dark:border-zinc-600 dark:bg-zinc-800">
                    <option value="none">Does not repeat</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly{{ $event_date ? ' on ' . \Carbon\CarbonImmutable::parse($event_date)->format('l') : '' }}</option>
                    <option value="monthly">Monthly{{ $event_date ? ' on the ' . $this->monthlyPatternLabel() : '' }}</option>
                    <option value="yearly">Yearly{{ $event_date ? ' on ' . \Carbon\CarbonImmutable::parse($event_date)->format('F j') : '' }}</option>
                    <option value="weekdays">Every weekday (Monday to Friday)</option>
                    <option value="custom">Custom...</option>
                </select>
                @error('recurrence') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        @if ($recurrence !== 'none')
            @if ($recurrence === 'custom')
                <div class="rounded-md border border-zinc-200 bg-zinc-50 p-3 space-y-3 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Custom recurrence</div>
                    <div class="space-y-1">
                        <label for="custom_interval" class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Repeat every</label>
                        <div class="grid grid-cols-2 gap-2">
                        <input id="custom_interval" type="number" min="1" max="52" wire:model.live="custom_interval" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-[#9E1D20] focus:ring-2 focus:ring-[#9E1D20]/20 dark:border-zinc-600 dark:bg-zinc-900">
                        <select wire:model.live="custom_unit" aria-label="Repeat unit" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-[#9E1D20] focus:ring-2 focus:ring-[#9E1D20]/20 dark:border-zinc-600 dark:bg-zinc-900">
                            <option value="day">day</option><option value="week">week</option><option value="month">month</option><option value="year">year</option>
                        </select>
                        </div>
                    </div>
                    @if ($custom_unit === 'week')
                        <fieldset class="space-y-2"><legend class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Repeat on</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day => $label)
                                    <label class="inline-flex min-w-10 items-center justify-center gap-1 rounded-md border border-zinc-300 bg-white px-2 py-2 text-xs dark:border-zinc-600 dark:bg-zinc-900"><input type="checkbox" value="{{ $day }}" wire:model.live="custom_weekdays" style="accent-color: #9E1D20">{{ $label }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                    @error('custom_weekdays') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            @endif
            <fieldset class="space-y-2"><legend class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Ends</legend>
                <label class="flex items-center gap-2 text-sm"><input type="radio" value="on" wire:model.live="recurrence_ends" style="accent-color: #9E1D20"> On a date</label>
                @if ($recurrence_ends === 'on')
                    <input type="date" wire:model.live="recurrence_until" aria-label="Recurrence end date" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-[#9E1D20] focus:ring-2 focus:ring-[#9E1D20]/20 dark:border-zinc-600 dark:bg-zinc-800">
                    @error('recurrence_until') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                @endif
                <label class="flex items-center gap-2 text-sm"><input type="radio" value="after" wire:model.live="recurrence_ends" style="accent-color: #9E1D20"> After a number of occurrences</label>
                @if ($recurrence_ends === 'after')
                    <div class="flex items-center gap-2"><input id="occurrences" type="number" min="2" max="52" wire:model.live="occurrences" aria-label="Number of occurrences" class="w-24 rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-[#9E1D20] focus:ring-2 focus:ring-[#9E1D20]/20 dark:border-zinc-600 dark:bg-zinc-800"> <span class="text-xs text-zinc-500">occurrences (2–52)</span></div>
                    @error('occurrences') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                @endif
            </fieldset>
            @error('recurrence') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            <p class="text-xs text-zinc-500">Recurring bookings are limited to one year and 52 event dates.</p>
            @if ($event_date)
                @php try { $previewDates = $this->recurrenceDates(); } catch (\Throwable $e) { $previewDates = []; } @endphp
                @if ($previewDates)
                    <div class="rounded-md bg-zinc-50 p-3 text-xs dark:bg-zinc-800">
                        <div class="font-medium">{{ count($previewDates) }} event dates</div>
                        <div class="mt-1">{{ collect($previewDates)->take(8)->map(fn ($date) => \Carbon\CarbonImmutable::parse($date)->format('M j, Y'))->join(' · ') }}@if (count($previewDates) > 8) · and {{ count($previewDates) - 8 }} more @endif</div>
                        @if (count($previewDates) > 8)
                            <details class="mt-2"><summary class="cursor-pointer font-medium">View all dates</summary>
                                <div class="mt-1 grid grid-cols-2 gap-1 sm:grid-cols-3">@foreach ($previewDates as $previewDate)<span>{{ \Carbon\CarbonImmutable::parse($previewDate)->format('M j, Y') }}</span>@endforeach</div>
                            </details>
                        @endif
                    </div>
                @endif
            @endif
            <p class="text-xs text-zinc-500">Every date will be submitted for approval. Dates that overlap approved bookings will be flagged for admin review.</p>
        @endif
        <div class="space-y-2 rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-700">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p>Requests with schedule conflicts can still be submitted. An admin must resolve a conflict before approval.</p>
                <flux:button size="sm" wire:click="checkSchedule" wire:loading.attr="disabled" wire:target="checkSchedule">
                    <span wire:loading.remove wire:target="checkSchedule">Check schedule</span>
                    <span wire:loading wire:target="checkSchedule">Checking...</span>
                </flux:button>
            </div>
            @if ($scheduleCheckAttempted && ($errors->has('resource_id') || $errors->has('event_date') || $errors->has('start_time') || $errors->has('end_time') || $errors->has('recurrence')))
                <p role="alert" class="rounded-md bg-red-50 p-2 text-red-800 dark:bg-red-950 dark:text-red-100">Select a room and enter a valid event date and time to check the schedule. Review any field errors above.</p>
            @endif
            @if ($scheduleConflictWarnings)
                <div class="rounded-md bg-amber-50 p-2 text-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    <div class="font-semibold">Conflict with approved booking</div>
                    @foreach ($scheduleConflictWarnings as $date => $names)
                        <div>{{ \Carbon\CarbonImmutable::parse($date)->format('M j, Y') }}: {{ implode(', ', $names) }}</div>
                    @endforeach
                </div>
            @elseif ($scheduleChecked)
                <p role="status" class="rounded-md bg-green-50 p-2 text-green-900 dark:bg-green-950 dark:text-green-100">No conflicts with approved bookings were found for the selected date{{ $recurrence === 'none' ? '' : 's' }}. You can submit the request for approval.</p>
            @else
                <p class="text-xs text-zinc-500">Use Check schedule to see current approved booking conflicts.</p>
            @endif
        </div>
    </div>

    <!-- Submit -->
    <flux:button wire:click="submitReservation" variant="primary" class="w-full py-3 text-sm font-semibold">
        Submit Reservation
    </flux:button>

</div>
