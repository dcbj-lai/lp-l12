@if ($reservation->soa_path && !$reservation->soa_locked)
    @php $billingEmail = $reservation->latestBillingEmail; $lastReminder = $reservation->latestPaymentReminder; @endphp
    @if ($lastReminder) <span class="text-xs text-zinc-500">Last reminder: {{ $lastReminder->sent_at->format('M j, Y g:i A') }}</span> @endif
@endif
