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

            // remove highlight after a few seconds
            setTimeout(() => {
                this.highlightId = null;
            }, 4000);
        }
    }
}" class="space-y-4">
    <!-- List -->
    <div class="space-y-3">
        @php
            $isFacilityAdmin = auth()->user()->hasRole('facility.admin');
        @endphp @forelse ($reservations as $res)
            <div :class="highlightId === 'res-{{ $res->id }}'
                ?
                'ring-2 ring-[#9E1D20] bg-[#9E1D20]/5 shadow-[0_0_20px_rgba(158,29,32,0.6),0_0_40px_rgba(158,29,32,0.4)]' :
                ''"
                id="res-{{ $res->id }}"
                class="relative p-4 rounded-lg border bg-white dark:bg-zinc-800 shadow-sm transition-all duration-500">

                <!-- ❌ Delete (Top Right - Admin Only) -->
                @if ($isFacilityAdmin)
                    <div class="absolute top-2 right-2">
                        <flux:button size="xs" variant="danger" wire:click="delete({{ $res->id }})"
                            icon="circle-x" />
                    </div>
                @endif

                <!-- Header Row -->
                <div class="flex items-start justify-between gap-3">

                    <div class="space-y-1">

                        <!-- Title + Status -->
                        <div class="flex items-center gap-2">
                            <div class="font-semibold text-zinc-800 dark:text-zinc-100">
                                {{ $res->title }}
                            </div>

                            <flux:badge size="sm"
                                color="{{ $res->status === 'approved' ? 'green' : ($res->status === 'rejected' ? 'red' : 'yellow') }}">
                                {{ ucfirst($res->status) }}
                            </flux:badge>
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

                <!-- Resource -->
                <div class="mt-2 text-sm text-gray-600">
                    {{ $res->resource?->name ?? '—' }}
                </div>

                <!-- Equipment -->
                @if ($res->equipment->count())
                    <div class="mt-1 text-xs text-gray-500">
                        Equipment: {{ $res->equipment->pluck('name')->join(', ') }}
                    </div>
                @endif

                <!-- Notes -->
                @if ($res->notes)
                    <div
                        class="mt-3 px-3 py-2 rounded-md border border-zinc-200 dark:border-zinc-700 
                    bg-zinc-50/60 dark:bg-zinc-900/40">

                        <div class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-zinc-500 mb-1">
                            Notes / Instructions
                        </div>

                        <div
                            class="text-sm text-zinc-600 italic dark:text-zinc-400 leading-relaxed whitespace-pre-line">
                            {{ $res->notes }}
                        </div>

                    </div>
                @endif

                <!-- Actions -->
                <div class="mt-3 flex items-center gap-2">

                    <flux:button size="sm" variant="primary" color="lime"
                        wire:click="{{ $res->status === 'approved' ? 'revoke(' . $res->id . ')' : 'approve(' . $res->id . ')' }}">
                        {{ $res->status === 'approved' ? 'Revoke' : 'Approve' }}
                    </flux:button>

                    <flux:button size="sm" variant="{{ $res->status === 'rejected' ? 'danger' : 'ghost' }}"
                        wire:click="reject({{ $res->id }})">
                        Reject
                    </flux:button>

                </div>

            </div>
        @empty
            <div class="text-center text-gray-500">
                No reservations found.
            </div>
        @endforelse

    </div>

</div>
