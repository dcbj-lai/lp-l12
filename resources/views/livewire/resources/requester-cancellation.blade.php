@if ($reservation->latestCancellationRequest)
    @php $cancellation = $reservation->latestCancellationRequest; @endphp
    <div class="rounded-md border p-3 text-sm space-y-1">
        <p class="font-medium">{{ ['review' => 'Requester cancellation · Awaiting facilities review', 'cancelled' => 'Cancelled at requester’s request', 'declined' => 'Requester cancellation declined'][$cancellation->status] }}</p>
        @if ($cancellation->reason)<p class="whitespace-pre-line">Reason: {{ $cancellation->reason }}</p>@endif
        @if ($cancellation->status === 'review' && $isFacilityAdmin)
            <flux:button size="sm" wire:click="selectCancellationReview({{ $cancellation->id }})">Review cancellation</flux:button>
        @endif
    </div>
@endif
