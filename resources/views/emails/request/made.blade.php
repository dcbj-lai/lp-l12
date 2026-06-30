@component('mail::message')
# New Request Submitted by {{ $request->user->name }}
**Type:**
{{ $request->typeLabel() }}<br>
**Reason:** {{ $request->reason }}<br>
@if ($request->isCreditCarryOver())
**Carry Over Credits:** {{ $request->number_of_days }}<br>
@else
**From:** {{ \Carbon\Carbon::parse($request->start_date)->toFormattedDateString() }}<br>
**To:** {{ \Carbon\Carbon::parse($request->end_date)->toFormattedDateString() }}<br>
**Days:** {{ $request->number_of_days }}<br>
@endif
@component('mail::button', ['url' => route('requests.show', $request->id)])
View Request
@endcomponent
Thanks,<br>The Life Portal Team
@endcomponent
