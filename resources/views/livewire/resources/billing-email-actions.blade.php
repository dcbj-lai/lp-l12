@if ($reservation->soa_path && !$reservation->soa_locked)
    @php $billingEmail = $reservation->latestBillingEmail; $lastReminder = $reservation->latestPaymentReminder; @endphp
    @if ($reservation->soa_email_pending)
        <span class="text-xs text-amber-700">{{ $reservation->soa_sent_at ? 'Updated SOA not sent' : 'SOA not sent' }}</span>
    @endif
    @if ($billingEmail)
        <span class="text-xs {{ $billingEmail->status === 'failed' ? 'text-red-700' : 'text-zinc-500' }}">Billing email: {{ $billingEmail->status }}@if ($billingEmail->sent_at) · {{ $billingEmail->sent_at->format('M j, Y g:i A') }}@endif</span>
    @endif
    @if ($lastReminder) <span class="text-xs text-zinc-500">Last reminder: {{ $lastReminder->sent_at->format('M j, Y g:i A') }}</span> @endif
@endif
