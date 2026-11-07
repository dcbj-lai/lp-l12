@if ($reservation->soa_path)
    @php
        $systemSoa = \App\Support\FacilitySoaFilename::generatedDocument($reservation);
        $soaFilename = \App\Support\FacilitySoaFilename::forAttachment($reservation);
        $soaQueued = $reservation->latestBillingEmail?->status === 'queued';
        $email = $reservation->latestBillingEmail;
        $currentSoaEmail = $email && in_array($email->kind, ['soa', 'updated_soa']) && ($email->snapshot['soa_path'] ?? null) === $reservation->soa_path;
        $isReplacement = $reservation->soaRevisions->first()?->replacement_path === $reservation->soa_path;
        $sentCurrentFile = !$reservation->soa_email_pending && $reservation->soa_sent_at && (!$isReplacement || $systemSoa?->sent_at
            || ($email && ($email->snapshot['soa_path'] ?? null) === $reservation->soa_path && (!$currentSoaEmail || $email->status === 'sent')));
        $soaStatus = $currentSoaEmail && in_array($email->status, ['queued', 'failed']) ? ucfirst($email->status) : ($sentCurrentFile ? 'Sent' : 'Uploaded');
    @endphp
    <div class="w-full space-y-2 border-y border-zinc-200 py-3 dark:border-zinc-700">
        <div class="flex items-start gap-2">
        <a class="min-w-0 flex-1 break-all text-blue-700 underline" href="{{ route('resources.reservations.billing-file', ['reservation' => $reservation->id, 'kind' => 'soa']) }}" target="_blank" rel="noopener">{{ $soaFilename }}</a>
        <div class="ml-auto flex shrink-0 items-center gap-1">
        @if ($systemSoa && $isFacilityAdmin && !$reservation->trashed() && $reservation->status === 'approved')
                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="$dispatch('create-soa', { reservationId: {{ $reservation->id }} })" :disabled="$soaQueued" aria-label="Edit SOA for reservation #{{ $reservation->id }}" title="{{ $soaQueued ? 'Wait for the queued billing email' : 'Edit SOA' }}" />
            <flux:button size="xs" variant="ghost" icon="trash" class="text-red-600" wire:click="selectForSoaRemoval({{ $reservation->id }})" :disabled="$reservation->soa_locked || $soaQueued" aria-label="Delete SOA for reservation #{{ $reservation->id }}" title="{{ $reservation->soa_locked ? 'Payment recorded; SOA cannot be removed' : ($soaQueued ? 'Wait for the queued billing email' : 'Delete SOA') }}" />
        @endif
        </div></div>
        <div><flux:badge size="sm" color="{{ $soaStatus === 'Sent' ? 'green' : ($soaStatus === 'Failed' ? 'red' : 'zinc') }}">{{ $soaStatus }}</flux:badge></div>
        @if($reservation->payment_due_at)<p class="text-xs text-zinc-500">Due date: {{ $reservation->payment_due_at->format('M j, Y') }}</p>@endif
    </div>
@endif
