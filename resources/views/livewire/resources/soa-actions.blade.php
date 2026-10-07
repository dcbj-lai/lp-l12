@php
    $billingEmail = $reservation->latestBillingEmail;
    $lastReminder = $reservation->latestPaymentReminder;
@endphp
<flux:dropdown position="bottom" align="start">
    <flux:button size="sm" icon:trailing="chevron-down">Billing</flux:button>
    <flux:menu>
        <flux:menu.item wire:click="selectForBilling({{ $reservation->id }})" :disabled="$reservation->soa_locked" title="{{ $reservation->soa_locked ? 'Payment recorded; SOA cannot be changed' : '' }}">{{ $reservation->soa_path ? 'Replace SOA' : 'Upload SOA' }}</flux:menu.item>
        @if ($reservation->soa_path)
            <flux:menu.item variant="danger" wire:click="selectForSoaRemoval({{ $reservation->id }})" :disabled="$reservation->soa_locked" title="{{ $reservation->soa_locked ? 'Payment recorded; SOA cannot be changed' : '' }}">Remove SOA</flux:menu.item>
            @if (!$reservation->soa_locked && $billingEmail?->status !== 'queued')
                @if ($reservation->soa_email_pending)
                    <flux:menu.item wire:click="selectBillingEmail({{ $reservation->id }}, 'soa')">{{ $reservation->soa_sent_at ? 'Send updated SOA' : 'Send SOA to requester' }}</flux:menu.item>
                @elseif ($reservation->billing_status === 'billed' && $reservation->payment_due_at)
                    <flux:menu.item wire:click="selectBillingEmail({{ $reservation->id }}, 'reminder')" :disabled="$lastReminder?->sent_at?->isToday() ?? false">Send payment reminder</flux:menu.item>
                @endif
            @endif
        @endif
    </flux:menu>
</flux:dropdown>
@include('livewire.resources.billing-email-actions', ['reservation' => $reservation])
