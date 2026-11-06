<x-layouts.facilities title="Manage your reservation" subtitle="Review your event details and choose any upcoming dates you need to cancel.">
    @if(session('cancellation_submitted'))
        <div role="status" class="facility-alert"><x-facilities.icon name="check" /><div>
            @forelse(session('cancellation_results', []) as $result)
                <p><strong>{{ $result['title'] }}</strong> · {{ $result['date'] }}:<br>{{ ['cancelled'=>'Cancelled. Allocations released.', 'review'=>'Cancellation requested. Your booking remains active while facilities reviews billing.', 'failed'=>'Could not cancel this date. It remains active; please retry or contact facilities.'][$result['status']] }}</p>
            @empty<p>No changes were made. Selected dates may already be cancelled, awaiting review, or past their start time.</p>@endforelse
        </div></div>
    @endif
    <div class="facility-stack">
    @foreach($bookings as $booking)
        @php
            $canCancelDate = !$booking->trashed() && $booking->start_datetime->isFuture() && in_array($booking->status, ['pending', 'approved']) && $booking->latestCancellationRequest?->status !== 'review';
            $canCancelSeries = $booking->recurrence_series_id && $seriesBookings->isNotEmpty();
            $statusLabel = $booking->trashed() ? ($booking->latestCancellationRequest?->status === 'cancelled' ? 'Cancelled' : 'Deleted') : ['pending'=>'For Approval', 'approved'=>'Approved', 'rejected'=>'Rejected'][$booking->status];
            $statusTone = $booking->trashed() ? 'neutral' : ['pending'=>'amber','approved'=>'green','rejected'=>'red'][$booking->status];
        @endphp
        <article class="facility-grid">
            <section class="facility-card">
                <div class="facility-icon-tile"><x-facilities.icon name="calendar" /></div>
                <div class="facility-card-heading"><span class="facility-eyebrow">Reservation #{{ $booking->id }}</span><span class="facility-badge facility-badge--{{ $statusTone }}">{{ $statusLabel }}</span></div>
                <h2>{{ $booking->title }}</h2>
                <dl class="facility-facts">
                    <div class="facility-wide"><dt>Event schedule</dt><dd>{{ $booking->start_datetime->format('M j, Y g:i A') }}–{{ $booking->end_datetime->format('M j, Y g:i A') }}</dd></div>
                    <div class="facility-wide"><dt>Rooms</dt><dd>{{ $booking->room_names }}</dd></div>
                    @if($booking->number_of_pax)<div><dt>Number of pax</dt><dd>{{ $booking->number_of_pax }}</dd></div><div><dt>Setup</dt><dd>{{ $booking->setup_arrangement }}</dd></div>@endif
                </dl>
                @if($booking->equipment->isNotEmpty())<div class="facility-divider"><span class="facility-label">Equipment</span><p>{{ $booking->equipment->map(fn($item) => $item->name.' × '.$item->pivot->quantity)->join(', ') }}</p></div>@endif
                @if($booking->notes)<div class="facility-divider"><span class="facility-label">Notes</span><p class="whitespace-pre-line">{{ $booking->notes }}</p></div>@endif
                @if($booking->recurrence_series_id)<div class="facility-divider"><span class="facility-badge facility-badge--blue">Recurring event</span><p class="facility-small facility-muted">{{ $booking->recurrence_position }} of {{ $booking->recurrence_total }} · {{ $booking->recurrence_label }}</p></div>@endif
            </section>
            <div class="facility-stack">
                @if($booking->latestCancellationRequest?->status === 'review')
                    <section class="facility-card facility-state"><div class="facility-icon-tile facility-icon-tile--amber"><x-facilities.icon name="clock" /></div><span class="facility-badge facility-badge--amber">Awaiting review</span><h2>Cancellation requested</h2><p>Your booking remains active while Facilities reviews the cancellation and any billing arrangements.</p></section>
                @endif
                @if($booking->recurrence_series_id ? $canCancelSeries : $canCancelDate)
                    <section class="facility-card">
                        <h2>Need to cancel?</h2><p class="facility-muted">{{ $booking->recurrence_series_id ? 'Choose one or more upcoming event dates below.' : 'You can request cancellation of this event date below.' }}</p>
                        <div class="facility-tip"><x-facilities.icon name="alert" /><p>Reservations with an SOA or payment require facilities review. A cancellation request does not automatically void billing or issue a refund.</p></div>
                        <details @if($errors->any()) open @endif class="facility-disclosure">
                            <summary>{{ $booking->recurrence_series_id ? 'Cancel reservation' : 'Cancel this date' }}</summary>
                            <form method="post" action="{{ route('facilities.requester.cancel', $token) }}" class="facility-disclosure-form">@csrf
                                <input type="hidden" name="reservation_id" value="{{ $booking->id }}">
                                @if($booking->recurrence_series_id)
                                    <input type="hidden" name="scope" value="selected">
                                    @if($canCancelSeries)
                                        <details open class="facility-date-list"><summary>Select or review upcoming series dates</summary>
                                            <p class="facility-small facility-muted">Select one or more dates to cancel. Select every date to cancel all remaining events. Started, rejected, deleted and already requested dates are excluded.</p>
                                            <ul>@foreach($seriesBookings as $date)<li>
                                                <label class="facility-date-choice"><input type="checkbox" name="selected_booking_ids[]" value="{{ $date->id }}" data-select-date @checked(in_array($date->id, old('selected_booking_ids', [])))>
                                                    <span>{{ $date->start_datetime->format('M j, Y g:i A') }}–{{ $date->end_datetime->format('g:i A') }}@if($date->soa_path || $date->paid_at || $date->payment_proof_path || $date->billing_status !== 'unbilled' || $date->payment_proofs_exists)<small>Requires facilities review</small>@endif</span>
                                                </label>
                                            </li>@endforeach</ul>
                                        </details>
                                    @endif
                                @else<input type="hidden" name="scope" value="date">@endif
                                <div class="facility-field"><label for="reason-{{ $booking->id }}">Reason (optional)</label><textarea id="reason-{{ $booking->id }}" name="reason" maxlength="2000" placeholder="Let Life College know why you need to cancel">{{ old('reason') }}</textarea></div>
                                <label class="facility-check"><input type="checkbox" name="confirm" value="1" required><span>I confirm cancellation of the selected event date(s).</span></label>
                                <button type="submit" class="facility-button facility-button--danger">Confirm cancellation</button>
                            </form>
                        </details>
                    </section>
                @elseif($booking->latestCancellationRequest?->status !== 'review')
                    <section class="facility-card facility-state"><div class="facility-icon-tile"><x-facilities.icon name="calendar" /></div><h2>Reservation details</h2><p>Cancellation is not available for this event date. For events that have started, cancelled dates, or billing questions, please contact Facilities.</p></section>
                @endif
            </div>
        </article>
    @endforeach
    </div>
</x-layouts.facilities>
