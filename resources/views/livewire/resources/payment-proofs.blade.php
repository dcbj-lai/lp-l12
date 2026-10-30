@foreach ($reservation->paymentProofs as $proof)
    <a href="{{ route('resources.reservations.billing-file', ['reservation' => $reservation->id, 'kind' => 'payment', 'proof' => $proof->id]) }}" target="_blank" rel="noopener" class="text-sm text-indigo-600 underline">Payment proof {{ $loop->iteration }}: {{ $proof->original_name }}</a>
@endforeach
@if ($revision = $reservation->soaRevisions->first())
    <p class="text-xs text-zinc-500">SOA replacement reason: {{ $revision->reason }}</p>
@endif
