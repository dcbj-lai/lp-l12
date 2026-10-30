@php $manageBookingUrl = app(\App\Services\FacilityRequesterCancellationService::class)->link($reservation); @endphp
@if ($manageBookingUrl)
    <p style="margin-top:20px"><a href="{{ $manageBookingUrl }}" style="display:inline-block;padding:10px 16px;background:#18181b;color:#fff;text-decoration:none;border-radius:6px">View / Cancel reservation</a></p>
    <p style="font-size:12px;color:#71717a">No login is required. Keep this link private: anyone with it can view this reservation and request cancellation of this date@if ($reservation->recurrence_series_id) or its upcoming recurring dates@endif. Opening the link does not cancel your booking.</p>
@endif
