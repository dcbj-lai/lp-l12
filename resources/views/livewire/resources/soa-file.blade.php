@if ($reservation->soa_path)
    @php
        $systemSoa = \App\Support\FacilitySoaFilename::generatedDocument($reservation);
        $soaFilename = \App\Support\FacilitySoaFilename::forAttachment($reservation);
        $soaQueued = $reservation->latestBillingEmail?->status === 'queued';
    @endphp
    <span class="inline-flex max-w-full items-center gap-1">
        <a class="break-all text-blue-700 underline" href="{{ route('resources.reservations.billing-file', ['reservation' => $reservation->id, 'kind' => 'soa']) }}" target="_blank" rel="noopener">{{ $soaFilename }}</a>
        @if ($systemSoa && $isFacilityAdmin && !$reservation->trashed() && $reservation->status === 'approved')
            @if ($reservation->soa_locked)
                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="selectForBilling({{ $reservation->id }})" aria-label="Replace SOA for reservation #{{ $reservation->id }}" title="Replace SOA with a file and reason; payment records are retained" />
            @else
                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="$dispatch('create-soa', { reservationId: {{ $reservation->id }} })" :disabled="$soaQueued" aria-label="Edit SOA for reservation #{{ $reservation->id }}" title="{{ $soaQueued ? 'Wait for the queued billing email' : 'Edit SOA' }}" />
            @endif
            <flux:button size="xs" variant="ghost" icon="trash" class="text-red-600" wire:click="selectForSoaRemoval({{ $reservation->id }})" :disabled="$reservation->soa_locked || $soaQueued" aria-label="Delete SOA for reservation #{{ $reservation->id }}" title="{{ $reservation->soa_locked ? 'Payment recorded; SOA cannot be removed' : ($soaQueued ? 'Wait for the queued billing email' : 'Delete SOA') }}" />
        @endif
    </span>
@endif
