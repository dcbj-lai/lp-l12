<div style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #222;">
    <p>
        @if($teacherName)
            Dear {{ $teacherName }},
        @else
            Dear Academics Team,
        @endif
    </p>

    <p>
        Please be informed that <strong>{{ $studentName }}</strong> had a consultation at the Guidance Office.
    </p>

    <p>
        <strong>Time In:</strong> {{ $timeInDisplay ?? 'N/A' }}<br>
        <strong>Time Out:</strong> {{ $timeOutDisplay ?? 'N/A' }}
    </p>

    <p>
        The student will be going home after the consultation.
    </p>

    <p>
        This message is sent for information and coordination purposes.
    </p>

    <p>
        Thank you.
    </p>

    <p>
        Respectfully,<br>
        Guidance Counselor
    </p>
</div>