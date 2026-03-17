Dear {{ $teacherName ?: 'Academics Team' }},

Good day.

Please be informed that {{ $studentName }} has completed their session at the Guidance Office and has been advised to go home and rest.

Time Out: {{ $timeOutDisplay }}

Status: {{ $releaseMode }}

@if($releaseDetails)
Details: {{ $releaseDetails }}
@endif

Thank you for your attention to this matter.

For any concerns or clarifications, please get in touch with the Guidance Office.

Respectfully,

Guidance Counselor