@component('mail::message')
# Your {{ $request->type }} Request Has Been {{ ucfirst($request->status) }}

**Type:** {{ $request->type }}<br>
**Reason:** {{ $request->reason }}<br>
**From:** {{ \Carbon\Carbon::parse($request->start_date)->toFormattedDateString() }}<br>
**To:** {{ \Carbon\Carbon::parse($request->end_date)->toFormattedDateString() }}<br>
**Days:** {{ $request->number_of_days }}<br>
**Approved By:** {{ ($request->approver)->name ?? '—' }}<br>
**Remarks:** {{ $request->remarks ?? 'No remarks provided.' }}<br>

{{-- @component('mail::button', ['url' => route('requests.show', $request->id)])
View Your Request
@endcomponent --}}

Thanks,<br>
The Life Portal Team
@endcomponent
