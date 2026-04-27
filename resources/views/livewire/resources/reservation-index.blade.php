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

                <!-- Actions -->
                @if ($res->status === 'pending')
                    <div class="mt-3 flex gap-2">

                        <flux:button size="sm" variant="primary" wire:click="approve({{ $res->id }})">
                            Approve
                        </flux:button>

                        <flux:button size="sm" variant="ghost" wire:click="reject({{ $res->id }})">
                            Reject
                        </flux:button>

                    </div>
                @endif

            </div>
        @empty
            <div class="text-center text-gray-500">
                No reservations found.
            </div>
        @endforelse

    </div>

</div>
