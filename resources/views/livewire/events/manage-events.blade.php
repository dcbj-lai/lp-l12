<div class="space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-100">Manage Events</h1>
        @unless ($showForm)
            <flux:button size="sm" variant="primary" icon="plus" wire:click="newEvent">
                New Event
            </flux:button>
        @endunless
    </div>

    {{-- ============ FORM ============ --}}
    @if ($showForm)
        <form wire:submit.prevent="save"
            class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800 space-y-4">

            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
                {{ $editingId ? 'Edit Event' : 'Create Event' }}
            </h2>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Title</label>
                <input type="text" wire:model="title"
                    class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                @error('title') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description</label>
                <textarea wire:model="description" rows="4"
                    class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white"></textarea>
                @error('description') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Location</label>
                <input type="text" wire:model="location"
                    class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Start</label>
                    <input type="datetime-local" wire:model="start_datetime"
                        class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">End</label>
                    <input type="datetime-local" wire:model="end_datetime"
                        class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                    @error('end_datetime') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">RSVP Deadline</label>
                    <input type="datetime-local" wire:model="rsvp_deadline"
                        class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                <select wire:model="status"
                    class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white">
                    <option value="draft">Draft (hidden from staff)</option>
                    <option value="published">Published (broadcast to all staff)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Custom RSVP Fields
                </label>
                @php($labelExamples = ['Dietary requirements', 'T-shirt size', 'Transport notes', 'Accessibility needs'])
                @php($instructionExamples = [
                    'List any allergies or dietary restrictions.',
                    'Enter your preferred shirt size.',
                    'Add carpool, shuttle, or parking notes.',
                    'Add access needs or seating requests.',
                ])
                <div class="space-y-3">
                    @for ($index = 0; $index < \App\Models\Event::MAX_CUSTOM_FIELDS; $index++)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                                    Field {{ $index + 1 }} Label
                                </label>
                                <input type="text" wire:model="customFieldLabels.{{ $index }}"
                                    placeholder="{{ $labelExamples[$index] }}"
                                    class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                                @error("customFieldLabels.$index") <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                                    Instruction / Placeholder
                                </label>
                                <input type="text" wire:model="customFieldInstructions.{{ $index }}"
                                    placeholder="{{ $instructionExamples[$index] }}"
                                    class="w-full text-sm border rounded-md px-3 py-2 dark:bg-zinc-700 dark:text-white" />
                                @error("customFieldInstructions.$index") <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Existing attachments (edit mode) --}}
            @if ($editingId)
                @php($current = \App\Models\Event::with('attachments')->find($editingId))
                @if ($current && $current->attachments->isNotEmpty())
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Current Instruction Files
                        </label>
                        <ul class="space-y-1">
                            @foreach ($current->attachments as $att)
                                <li class="flex items-center justify-between text-sm bg-zinc-100 dark:bg-zinc-700 rounded-md px-3 py-2">
                                    <span class="truncate">{{ $att->original_name ?? $att->file_path }}</span>
                                    <button type="button" wire:click="deleteAttachment({{ $att->id }})"
                                        class="text-red-500 hover:text-red-400 text-xs">Remove</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Add Instruction Files (PDF, Word, Excel, images — max 10MB each)
                </label>
                <input type="file" multiple wire:model="attachments" class="w-full text-sm" />
                @error('attachments.*') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                <div wire:loading wire:target="attachments" class="text-xs text-zinc-500 mt-1">Uploading…</div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <flux:button type="submit" size="sm" variant="primary">Save</flux:button>
                <flux:button type="button" size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
            </div>
        </form>
    @endif

    {{-- ============ LIST ============ --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900 text-left text-gray-500">
                <tr>
                    <th class="py-2 px-4">Title</th>
                    <th class="py-2 px-4">When</th>
                    <th class="py-2 px-4">Status</th>
                    <th class="py-2 px-4 text-center">Attending</th>
                    <th class="py-2 px-4 text-center">Files</th>
                    <th class="py-2 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="py-2 px-4 font-medium text-zinc-800 dark:text-zinc-100">{{ $event->title }}</td>
                        <td class="py-2 px-4 text-gray-500">
                            {{ optional($event->start_datetime)->format('M d, Y g:i A') ?? '—' }}
                        </td>
                        <td class="py-2 px-4">
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ $event->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-200 text-zinc-600' }}">
                                {{ ucfirst($event->status) }}
                            </span>
                        </td>
                        <td class="py-2 px-4 text-center">{{ $event->attending_count }}</td>
                        <td class="py-2 px-4 text-center">{{ $event->attachments->count() }}</td>
                        <td class="py-2 px-4">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="xs" variant="ghost" icon="eye"
                                    href="{{ route('events.show', $event->id) }}">View</flux:button>
                                <flux:button size="xs" variant="ghost" icon="download"
                                    href="{{ route('events.registrants.csv', $event->id) }}">CSV</flux:button>
                                <flux:button size="xs" variant="ghost" icon="download"
                                    href="{{ route('events.registrants.pdf', $event->id) }}">PDF</flux:button>
                                <flux:button size="xs" variant="ghost" icon="pencil"
                                    wire:click="edit({{ $event->id }})">Edit</flux:button>
                                <flux:button size="xs" variant="ghost" icon="trash"
                                    wire:click="delete({{ $event->id }})"
                                    wire:confirm="Delete this event? This removes its files and RSVPs.">Delete</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-gray-500">No events yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ INVITE-ON-PUBLISH MODAL ============ --}}
    @if ($showInviteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white dark:bg-zinc-800 shadow-xl flex flex-col max-h-[85vh]">

                <div class="p-5 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Invite users</h2>
                    <p class="text-sm text-gray-500">The event is now published. Select who to invite by email.</p>
                </div>

                <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:button size="xs" variant="ghost" wire:click="selectAllInvitees">Select All</flux:button>
                        <flux:button size="xs" variant="ghost" wire:click="clearInvitees">Clear</flux:button>
                    </div>
                    <span class="text-xs text-gray-500">{{ count($selectedInvitees) }} selected</span>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-3">
                    <ul class="space-y-1">
                        @foreach ($invitees as $user)
                            <li>
                                <label class="flex items-center gap-3 rounded-md px-2 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700/60 cursor-pointer">
                                    <input type="checkbox" wire:model.live="selectedInvitees" value="{{ $user->id }}"
                                        class="rounded border-zinc-300 dark:bg-zinc-700" />
                                    <span class="text-sm text-zinc-800 dark:text-zinc-200">
                                        {{ $user->preferred_name ?? $user->name }}
                                        <span class="text-xs text-gray-400">· {{ $user->email }}</span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="p-5 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end gap-3">
                    <flux:button size="sm" variant="ghost" wire:click="closeInviteModal">Skip</flux:button>
                    <flux:button size="sm" variant="primary" wire:click="sendInvites"
                        :disabled="count($selectedInvitees) === 0">
                        Send Invites
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
