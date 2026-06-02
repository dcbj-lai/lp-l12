<div>
    @php($closed = $event->rsvpClosed())
    @php($customFieldLabels = $event->customFieldLabels())
    @php($customFieldInstructions = $event->customFieldInstructions())

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800">
        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 mb-3">Will you attend?</h3>

        @if ($closed)
            <p class="text-xs text-amber-600 dark:text-amber-400 mb-3">
                RSVP closed on {{ $event->rsvp_deadline->format('M d, Y g:i A') }}.
            </p>
        @endif

        @if (!$closed && ($status === 'attending' || $customFieldLabels !== []))
            <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                        Number of guests
                    </label>
                    <input type="number" min="0" wire:model.live="guestCount"
                        class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                    @error('guestCount') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                @foreach ($customFieldLabels as $index => $label)
                    @php($instruction = $customFieldInstructions[$index] ?? '')
                    <div>
                        <label class="flex items-center gap-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                            <span>{{ $label }}</span>
                            @if ($instruction !== '')
                                <span class="relative inline-flex group">
                                    <button type="button"
                                        aria-label="{{ $label }} instructions"
                                        class="inline-flex h-4 w-4 items-center justify-center rounded-full text-zinc-400 hover:text-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:hover:text-zinc-100">
                                        <flux:icon.info class="h-3.5 w-3.5" />
                                    </button>
                                    <span role="tooltip"
                                        class="pointer-events-none absolute left-1/2 bottom-full z-20 mb-2 hidden w-56 -translate-x-1/2 rounded-md bg-zinc-900 px-3 py-2 text-xs font-normal leading-relaxed text-white shadow-lg group-hover:block group-focus-within:block">
                                        {{ $instruction }}
                                    </span>
                                </span>
                            @endif
                        </label>
                        <input type="text" wire:model.live="customFieldAnswers.{{ $index }}"
                            placeholder="{{ $instruction }}"
                            class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                        @error("customFieldAnswers.$index") <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mb-4 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <h4 class="text-xs font-semibold text-zinc-700 dark:text-zinc-200 mb-3">
                Emergency &amp; Health
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                        Emergency Contact Person
                    </label>
                    <input type="text" wire:model.live="emergency_contact_name"
                        placeholder="Full name"
                        class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                    @error('emergency_contact_name') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                        Relationship
                    </label>
                    <select wire:model.live="emergency_contact_relationship"
                        class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
                        <option value="">Select relationship</option>
                        @foreach ($relationshipOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('emergency_contact_relationship') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                        Emergency Contact Number
                    </label>
                    <input type="text" wire:model.live="emergency_contact_phone"
                        placeholder="+639171234567"
                        class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                    @error('emergency_contact_phone') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                        Dietary Preference
                    </label>
                    <input type="text" wire:model.live="dietary_preference"
                        placeholder="e.g. None, Vegetarian, Halal"
                        class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                    @error('dietary_preference') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                        Allergies / Medical Notes
                    </label>
                    <textarea wire:model.live="medical_notes" rows="3"
                        placeholder="Allergies or medical conditions relevant to events"
                        class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white"></textarea>
                    @error('medical_notes') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <flux:button class="mt-3" size="xs" variant="ghost" wire:click="saveProfileDetails">
                Save details
            </flux:button>
        </div>

        <div class="flex items-center gap-3">
            <flux:button size="sm" wire:click="respond('attending')" :disabled="$closed"
                :variant="$status === 'attending' ? 'primary' : 'ghost'"
                icon="check">
                Attending
            </flux:button>

            <flux:button size="sm" wire:click="respond('not_attending')" :disabled="$closed"
                :variant="$status === 'not_attending' ? 'primary' : 'ghost'"
                icon="x-mark">
                Not Attending
            </flux:button>
        </div>

        @if ($status === 'attending')
            <div class="mt-2">
                <flux:button size="xs" variant="ghost" wire:click="respond('attending')" :disabled="$closed">
                    Update RSVP
                </flux:button>
            </div>
        @endif

        @if ($status)
            <p class="mt-3 text-xs text-zinc-500">
                Current response:
                <span class="font-semibold {{ $status === 'attending' ? 'text-emerald-500' : 'text-red-400' }}">
                    {{ $status === 'attending' ? 'Attending' : 'Not attending' }}
                </span>
            </p>
        @endif
    </div>
</div>
