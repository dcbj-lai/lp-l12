@component('mail::message')
# Your {{ $request->type }} Request Has Been {{ ucfirst($request->status) }}

**Type:** {{ $request->type }}<br>
**Reason:** {{ $request->reason }}<br>
**From:** {{ \Carbon\Carbon::parse($request->start_date)->toFormattedDateString() }}<br>
**To:** {{ \Carbon\Carbon::parse($request->end_date)->toFormattedDateString() }}<br>
**Days:** {{ $request->number_of_days }}<br>
**Approved By:** {{ ($request->approver)->name ?? '—' }}<br>
**Remarks:** {{ $request->remarks ?? 'No remarks provided.' }}<br>
Thanks,<br>
The Life Portal Team
@endcomponent
