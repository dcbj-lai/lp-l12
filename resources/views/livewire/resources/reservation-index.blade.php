<div x-data="{
    highlightId: null,
    init() {
        const hash = window.location.hash;

        if (hash && hash.startsWith('#res-')) {
            this.highlightId = hash.replace('#', '');

            this.$nextTick(() => {
                const el = document.getElementById(this.highlightId);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            setTimeout(() => this.highlightId = null, 4000);
        }
    }
}" class="space-y-4">

    <div class="space-y-3">
        @php
            $isFacilityAdmin = auth()->user()->hasRole('facility.admin');
        @endphp

        @forelse ($reservations as $res)
            <div id="res-{{ $res->id }}"
                :class="highlightId === 'res-{{ $res->id }}'
                    ?
                    'ring-2 ring-[#9E1D20] bg-[#9E1D20]/5 shadow-[0_0_20px_rgba(158,29,32,0.6),0_0_40px_rgba(158,29,32,0.4)]' :
                    ''"
                class="relative p-4 rounded-lg border bg-white dark:bg-zinc-800 shadow-sm transition-all duration-500">

                {{-- Delete --}}
                @if ($isFacilityAdmin)
                    <div class="absolute top-2 right-2">
                        <flux:modal.trigger name="delete-reservation-{{ $res->id }}">
                            <flux:button size="xs" variant="danger" icon="circle-x" />
                        </flux:modal.trigger>
                    </div>
                @endif

                {{-- Header --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">

                        <div class="flex items-center gap-2">
                            <div class="font-semibold text-zinc-800 dark:text-zinc-100">
                                {{ $res->title }}
                            </div>

                            <flux:badge size="sm"
                                color="{{ $res->status === 'approved' ? 'green' : ($res->status === 'rejected' ? 'red' : 'yellow') }}">
                                {{ ucfirst($res->status) }}
                            </flux:badge>

                            @if ($res->approval_note)
                                <span title="{{ $res->approval_note }}">
                                    <flux:icon name="document-text"
                                        class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help" />
                                </span>
                            @endif
                        </div>

                        <div class="text-xs text-gray-500">
                            {{ $res->requester_email ?? 'Internal User' }}
                        </div>

                        <div class="text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($res->start_datetime)->format('M d, h:i A') }}
                            →
                            {{ \Carbon\Carbon::parse($res->end_datetime)->format('M d, h:i A') }}
                        </div>

                    </div>
                </div>

                {{-- Resource --}}
                <div class="mt-2 text-sm text-gray-600">
                    {{ $res->resource?->name ?? '—' }}
                </div>

                {{-- Equipment --}}
                @if ($res->equipment->count())
                    <div class="mt-1 text-xs text-gray-500">
                        Equipment: {{ $res->equipment->pluck('name')->join(', ') }}
                    </div>
                @endif

                {{-- Notes --}}
                @if ($res->notes)
                    <div class="mt-3 px-3 py-2 rounded-md border bg-zinc-50/60 dark:bg-zinc-900/40">
                        <div class="text-[11px] uppercase text-gray-400 mb-1">
                            Notes / Instructions
                        </div>
                        <div class="text-sm italic whitespace-pre-line">
                            {{ $res->notes }}
                        </div>
                    </div>
                @endif

                {{-- Attachment --}}
                @if ($res->attachment_path)
                    <div class="mt-3 px-3 py-2 rounded-md border bg-zinc-50/60 dark:bg-zinc-900/40">
                        <div class="text-[11px] uppercase text-gray-400 mb-1">
                            Instruction Attachment
                        </div>

                        <a href="{{ Storage::disk('s3')->url($res->attachment_path) }}" target="_blank"
                            class="text-sm text-blue-600 hover:underline break-all">
                            {{ basename($res->attachment_path) }}
                        </a>
                    </div>
                @endif

                <div class="mt-4 flex items-center gap-3">

                    {{-- Approve --}}
                    @if ($res->status !== 'approved')
                        <flux:modal.trigger name="approve-reservation">
                            <flux:button size="sm" variant="primary" color="lime"
                                class="min-w-[90px] justify-center"
                                wire:click="
                    $set('reservationId', {{ $res->id }});
                    $set('approvalNote', null);
                ">
                                Approve
                            </flux:button>
                        </flux:modal.trigger>
                    @endif

                    {{-- Reject --}}
                    <flux:modal.trigger name="reject-reservation">
                        <flux:button size="sm" variant="danger" class="min-w-[90px] justify-center"
                            wire:click="
            $set('reservationId', {{ $res->id }});
            $set('approvalNote', null);
        ">
                            Reject
                        </flux:button>
                    </flux:modal.trigger>

                </div>
            </div>

            {{-- Delete Modal --}}
            <flux:modal name="delete-reservation-{{ $res->id }}" size="sm">
                <div class="p-4 space-y-4">
                    <h2 class="text-lg font-semibold">Delete Reservation</h2>

                    <p class="text-sm text-gray-500">
                        Delete <strong>{{ $res->title }}</strong>? This cannot be undone.
                    </p>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>

                        <flux:button variant="danger" wire:click="delete({{ $res->id }})">
                            Delete
                        </flux:button>
                    </div>
                </div>
            </flux:modal>

        @empty
            <div class="text-center text-gray-500">
                No reservations found.
            </div>
        @endforelse
    </div>

    <flux:modal name="approve-reservation" size="md">

        <div class="p-4 space-y-4">

            <h2 class="text-lg font-semibold">
                Approve Reservation
            </h2>

            <textarea wire:model.defer="approvalNote" rows="4" class="w-full rounded-md border-gray-300 text-sm"
                placeholder="Add note (optional)"></textarea>

            <div class="flex justify-end gap-2">

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" color="lime" wire:click="confirmApprove" wire:loading.attr="disabled">
                    Confirm Approval
                </flux:button>

            </div>

        </div>

    </flux:modal>
    <flux:modal name="reject-reservation" size="md">

        <div class="p-4 space-y-4">

            <h2 class="text-lg font-semibold text-red-600">
                Reject Reservation
            </h2>

            <textarea wire:model.defer="approvalNote" rows="4" class="w-full rounded-md border-gray-300 text-sm"
                placeholder="Reason (required)"></textarea>

            <div class="flex justify-end gap-2">

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="confirmReject" wire:loading.attr="disabled">
                    Confirm Rejection
                </flux:button>

            </div>

        </div>

    </flux:modal>

</div>
