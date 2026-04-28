<div class="space-y-4">

    <!-- Header -->
    <div>
        <h1 class="text-xl md:text-2xl font-semibold text-zinc-800 dark:text-zinc-100">
            Resource Reservations
        </h1>
        <p class="text-sm text-gray-500">
            Approval dashboard
        </p>
    </div>

    <!-- List -->
    <div class="space-y-3">

        @forelse ($reservations as $res)
            <div class="p-4 rounded-lg border bg-white dark:bg-zinc-800 shadow-sm">

                <div class="flex justify-between items-start gap-3">

                    <div class="space-y-1">
                        <div class="font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ $res->title }}
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

                    <!-- Status -->
                    <div>
                        @if ($res->status === 'pending')
                            <span class="text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-700">Pending</span>
                        @elseif ($res->status === 'approved')
                            <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-700">Approved</span>
                        @else
                            <span class="text-xs px-2 py-1 rounded bg-red-100 text-red-700">Rejected</span>
                        @endif
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

                    <!-- Primary Action -->
                    <flux:button size="sm" variant="primary"
                        wire:click="{{ $res->status === 'approved' ? 'revoke(' . $res->id . ')' : 'approve(' . $res->id . ')' }}">
                        {{ $res->status === 'approved' ? 'Revoke' : 'Approve' }}
                    </flux:button>

                    <!-- Reject -->
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
