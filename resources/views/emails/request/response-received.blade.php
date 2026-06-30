@component('mail::message')
{{ $request->user->name }},<br><br>
Your
{{ $request->typeLabel() }}
request has been {{ ucfirst($request->status) }}.

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
**Approved By:** {{ ($request->approver)->name ?? '—' }}<br>
**Remarks:** {{ $request->remarks ?? 'No remarks provided.' }}<br><br>
Thanks,<br>
The Life Portal Team
@endcomponent
