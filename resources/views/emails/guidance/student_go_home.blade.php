<div style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #222;">
    <p>
        @if($teacherName)
            Dear {{ $teacherName }},
        @else
            Dear Academics Team,
        @endif
    </p>

    <p>
        This is to confirm that <strong>{{ $studentName }}</strong> attended a session with the Guidance and Wellness Office on {{ $dateDisplay ?? 'N/A' }}.
    </p>

    <p>
        <strong>Time In:</strong> {{ $timeInDisplay ?? 'N/A' }}<br>
        <strong>Time Out:</strong> {{ $timeOutDisplay ?? 'N/A' }}
    </p>

    <p>
        The student has been advised to go home after the session.
    </p>

    <p>
        Please note that this message is intended for information and coordination purposes only.
    </p>

    <p>
        Thank you for your understanding and support.
    </p>

    <p>
        Respectfully,<br>
        Guidance Counselor
    </p>
</div>