@component('mail::message')
# New Request Submitted by {{ $request->user->name }}
**Type:** {{ $request->type }}<br>
**Reason:** {{ $request->reason }}<br>
**From:** {{ \Carbon\Carbon::parse($request->start_date)->toFormattedDateString() }}<br>
**To:** {{ \Carbon\Carbon::parse($request->end_date)->toFormattedDateString() }}<br>
**Days:** {{ $request->number_of_days }}<br>
@component('mail::button', ['url' => route('requests.show', $request->id)])
View Request
@endcomponent
Thanks,<br>The Life Portal Team
@endcomponent
