@if($teacherName)
Dear {{ $teacherName }},
@else
Dear Academics Team,
@endif

Please be informed that {{ $studentName }} had a consultation at the Guidance Office.

Time In: {{ $timeInDisplay ?? 'N/A' }}
Time Out: {{ $timeOutDisplay ?? 'N/A' }}

The student is now returning to class.

This message is sent for information and coordination purposes.

Thank you.

Respectfully,

Guidance Counselor