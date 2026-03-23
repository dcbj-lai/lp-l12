@if($teacherName)
Dear {{ $teacherName }},
@else
Dear Academics Team,
@endif

Please be informed that {{ $studentName }} had a consultation at the Guidance Office.

Time In: {{ $timeInDisplay ?? 'N/A' }}
Time Out: {{ $timeOutDisplay ?? 'N/A' }}

The student will be going home after the consultation.

Going Home Method:
@if($goingHomeMethod === 'fetcher')
With fetcher
@elseif($goingHomeMethod === 'self')
By oneself
@else
N/A
@endif

@if($goingHomeMethod === 'fetcher')
Fetcher Name: {{ $fetcherName ?? 'N/A' }}
@endif

@if($goingHomeMethod === 'self')
Approved By: {{ $selfApprovedBy ?? 'N/A' }}
@endif

This message is sent for information and coordination purposes.

Thank you.

Respectfully,

Guidance Counselor