{{-- resources/views/emails/clinic/student_resume_class.blade.php --}}

<div style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #222;">
    <p>
        @if($teacherName)
            Dear {{ $teacherName }},
        @else
            Dear Academics Team,
        @endif
    </p>

    <p>
        This is to confirm that <strong>{{ $studentName }}</strong> visited the Clinic on {{ $dateDisplay ?? 'N/A' }}.
    </p>

    <p>
        <strong>Time In:</strong> {{ $timeInDisplay ?? 'N/A' }}<br>
        <strong>Time Out:</strong> {{ $timeOutDisplay ?? 'N/A' }}
    </p>

    <p>
        The student has been cleared to return to class.
    </p>

    <p>
        Please note that this message is intended for information and coordination purposes only.
    </p>

    <p>
        Thank you for your understanding and support.
    </p>

    <p>
        Respectfully,<br>
        --<br>
        Clinic Nurse<br>
        Center for Campus Health and Safety<br>
        Life College International<br>
    </p>
</div>