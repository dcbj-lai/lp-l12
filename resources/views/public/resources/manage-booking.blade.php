<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex,nofollow"><title>Manage reservation · LP</title>@vite(['resources/css/app.css'])</head>
<body class="bg-zinc-50 text-zinc-800"><main class="mx-auto max-w-3xl p-6 space-y-5">
    <h1 class="text-2xl font-semibold">Manage your reservation</h1>
    <p>All times are Asia/Manila. This private link shows the reservation below. For a recurring event, select the upcoming dates you want to cancel. Reservations with an SOA or payment require facilities review.</p>
    @if ($errors->any())<div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if (session('cancellation_submitted'))
        <div role="status" class="rounded-lg border bg-white p-4">
            @forelse (session('cancellation_results', []) as $result)
                <p>{{ $result['title'] }} · {{ $result['date'] }}:
                    {{ ['cancelled' => 'Cancelled. Allocations released.', 'review' => 'Cancellation requested. Your booking remains active while facilities reviews billing.', 'failed' => 'Could not cancel this date. It remains active; please retry or contact facilities.'][$result['status']] }}</p>
            @empty<p>No changes were made. Selected dates may already be cancelled, awaiting review, or past their start time.</p>@endforelse
        </div>
    @endif
    @foreach ($bookings as $booking)
        <article class="rounded-lg border bg-white p-5 space-y-2">
            <h2 class="text-lg font-semibold">{{ $booking->title }}</h2>
            <p>{{ $booking->start_datetime->format('M j, Y g:i A') }}–{{ $booking->end_datetime->format('M j, Y g:i A') }}</p>
            <p>Rooms: {{ $booking->room_names }}</p>
            <p>Status: {{ $booking->trashed() ? ($booking->latestCancellationRequest?->status === 'cancelled' ? 'Cancelled' : 'Deleted') : ['pending' => 'For Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'][$booking->status] }}</p>
            @if ($booking->number_of_pax)<p>{{ $booking->number_of_pax }} pax · {{ $booking->setup_arrangement }}</p>@endif
            @if ($booking->equipment->isNotEmpty())<p>Equipment: {{ $booking->equipment->map(fn ($item) => $item->name.' × '.$item->pivot->quantity)->join(', ') }}</p>@endif
            @if ($booking->notes)<p class="whitespace-pre-line">Notes: {{ $booking->notes }}</p>@endif
            @if ($booking->recurrence_series_id)<p class="text-sm text-zinc-500">Recurring event · {{ $booking->recurrence_position }} of {{ $booking->recurrence_total }} · {{ $booking->recurrence_label }}</p>@endif
            @if ($booking->latestCancellationRequest?->status === 'review')
                <p class="text-amber-800">Cancellation requested · Awaiting facilities review</p>
            @endif
            @php
                $canCancelDate = !$booking->trashed() && $booking->start_datetime->isFuture() && in_array($booking->status, ['pending', 'approved']) && $booking->latestCancellationRequest?->status !== 'review';
                $canCancelSeries = $booking->recurrence_series_id && $seriesBookings->isNotEmpty();
            @endphp
            @if ($booking->recurrence_series_id ? $canCancelSeries : $canCancelDate)
                <details @if ($errors->any()) open @endif class="rounded-md border p-3"><summary class="cursor-pointer font-medium text-red-700">{{ $booking->recurrence_series_id ? 'Cancel reservation' : 'Cancel this date' }}</summary>
                    <form method="POST" action="{{ route('facilities.requester.cancel', $token) }}" class="mt-3 space-y-3">@csrf
                        <input type="hidden" name="reservation_id" value="{{ $booking->id }}">
                        @if ($booking->recurrence_series_id)
                            <input type="hidden" name="scope" value="selected">
                            @if ($canCancelSeries)
                                <details open class="rounded-md border p-3"><summary class="cursor-pointer">Select or review upcoming series dates</summary>
                                    <p class="mt-2 text-sm">Select one or more dates to cancel. Select every date to cancel all remaining events. Started, rejected, deleted and already requested dates are excluded.</p>
                                    <ul class="mt-2 space-y-1 text-sm">
                                        @foreach ($seriesBookings as $date)
                                            <li><label class="flex items-start gap-2 rounded-md p-2 hover:bg-zinc-50"><input class="mt-1" type="checkbox" name="selected_booking_ids[]" value="{{ $date->id }}" data-select-date @checked(in_array($date->id, old('selected_booking_ids', [])))>
                                                <span>{{ $date->start_datetime->format('M j, Y g:i A') }}–{{ $date->end_datetime->format('g:i A') }}@if ($date->soa_path || $date->paid_at || $date->payment_proof_path || $date->billing_status !== 'unbilled' || $date->payment_proofs_exists) · Requires facilities review @endif</span></label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        @else
                            <input type="hidden" name="scope" value="date">
                        @endif
                        <label class="block">Reason (optional)<textarea name="reason" maxlength="2000" class="block w-full rounded-md border p-2">{{ old('reason') }}</textarea></label>
                        <label class="block"><input type="checkbox" name="confirm" value="1" required> I confirm cancellation of the selected event date(s).</label>
                        <button class="rounded-md bg-red-600 px-4 py-2 text-white">Confirm cancellation</button>
                    </form>
                </details>
            @endif
        </article>
    @endforeach
    <p class="text-sm">For billing, refunds, expired links, or events that have started, contact <a href="mailto:events.facilities@life.edu.ph" class="underline">events.facilities@life.edu.ph</a>.</p>
</main>
</body></html>
