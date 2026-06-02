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

        @if (!$closed && $status !== 'not_attending' && ($status === 'attending' || $customFieldLabels !== []))
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
