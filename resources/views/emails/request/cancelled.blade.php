@component('mail::message')
# Request Cancelled

**{{ $request->user->name }}** has cancelled their
{{ $request->type === 'PTO' ? 'Leave' : ($request->type === 'WFH' ? 'Work from home' : strtolower($request->type)) }}
request.<br>

**Reason:** {{ $request->reason }}<br>
**From:** {{ \Carbon\Carbon::parse($request->start_date)->toFormattedDateString() }}<br>
**To:** {{ \Carbon\Carbon::parse($request->end_date)->toFormattedDateString() }}<br>
**Days:** {{ $request->number_of_days }}<br>


@component('mail::button', ['url' => route('requests.show', $request->id)])
View Request
@endcomponent

Thanks,<br>
The Life Portal Team
@endcomponent
