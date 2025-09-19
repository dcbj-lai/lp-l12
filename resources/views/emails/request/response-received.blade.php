@component('mail::message')
{{ $request->user->name }},<br><br>
Your
{{ $request->type === 'PTO' ? 'leave' : ($request->type === 'WFH' ? 'work from home' : strtolower($request->type)) }}
request has been {{ ucfirst($request->status) }}.

**Type:** {{ $request->type }}<br>
**Reason:** {{ $request->reason }}<br>
**From:** {{ \Carbon\Carbon::parse($request->start_date)->toFormattedDateString() }}<br>
**To:** {{ \Carbon\Carbon::parse($request->end_date)->toFormattedDateString() }}<br>
**Days:** {{ $request->number_of_days }}<br>
**Approved By:** {{ ($request->approver)->name ?? '—' }}<br>
**Remarks:** {{ $request->remarks ?? 'No remarks provided.' }}<br><br>
Thanks,<br>
The Life Portal Team
@endcomponent
