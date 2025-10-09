<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Visit Confirmed</title>
</head>

<body style="font-family:Arial, sans-serif; background-color:#f8fafc; padding:20px;">
    <table width="100%" cellpadding="0" cellspacing="0"
        style="max-width:600px; margin:auto; background:white; border-radius:8px; padding:24px;">
        <tr>
            <td>
                <h1 style="color:#2d3748;">Visit Confirmed</h1>
                <p>Hi {{ $visitor->full_name }},</p>

                <p>Your visit to <strong>Life Academy</strong> has been <strong>pre-approved</strong>.</p>

                <ul>
                    <li><strong>Date:</strong> {{ \Carbon\Carbon::parse($visitor->visit_date)->format('F j, Y') }}</li>
                    <li><strong>Purpose:</strong> {{ $visitor->purpose }}</li>
                    <li><strong>Host:</strong> {{ $visitor->visitedUser?->name ?? 'Unassigned' }}</li>
                    <li><strong>Meetup Notes:</strong> {{ $visitor->meetup_spot ?: 'No special instructions.' }}</li>
                </ul>

                <div style="background:#edf2f7; padding:12px; border-radius:6px; text-align:center;">
                    Present attached QR Code at the reception desk for check-in.
                </div>

                <p>Thanks,<br><strong>Your Team at LAIC</strong></p>
            </td>
        </tr>
    </table>
</body>

</html>
