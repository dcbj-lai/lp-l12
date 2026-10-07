@if ($reservation->soa_path && !$reservation->soa_locked)
    @php $billingEmail = $reservation->latestBillingEmail; $lastReminder = $reservation->latestPaymentReminder; @endphp
    @if ($billingEmail)
        <span class="text-xs {{ $billingEmail->status === 'failed' ? 'text-red-700' : 'text-zinc-500' }}">Billing email: {{ $billingEmail->status }}@if ($billingEmail->sent_at) · {{ $billingEmail->sent_at->format('M j, Y g:i A') }}@endif</span>
    @endif
    @if ($lastReminder) <span class="text-xs text-zinc-500">Last reminder: {{ $lastReminder->sent_at->format('M j, Y g:i A') }}</span> @endif
@endif
