<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Visit Confirmed</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f0f4f8; padding: 20px;">

    <table width="100%" cellpadding="0" cellspacing="0"
        style="max-width:600px; margin:auto; background:white; border-radius:12px; padding:30px; box-shadow:0 4px 15px rgba(0,0,0,0.05);">
        <tr>
            <td style="text-align:center;">
                <!-- Logo -->
                <img src="{{ config('app.url') }}/images/lai-logo.png" alt="Life Academy Logo"
                    style="width:120px; margin-bottom:20px;">

                <h1 style="color:#1f2937; font-size:24px; margin-bottom:10px;">✅ Visit Confirmed</h1>
                <p style="color:#4b5563; font-size:16px;">Hi <strong>{{ $visitor->full_name }}</strong>,</p>

                <p style="color:#4b5563; font-size:16px;">
                    Here are the details to your upcoming visit to <strong style="color:#1390B4;">LAIC</strong>.
                </p>

                <table width="100%" cellpadding="8" cellspacing="0"
                    style="margin:20px 0; border-radius:8px; background:#f3f4f6; text-align:left;">
                    <tr>
                        <td style="font-weight:bold; width:130px;">Date:</td>
                        <td>{{ \Carbon\Carbon::parse($visitor->visit_date)->format('F j, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;">Purpose:</td>
                        <td>{{ $visitor->purpose ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;">Host:</td>
                        <td>{{ $visitor->visitedUser?->name ?? 'Unassigned' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;">Meetup Notes:</td>
                        <td>{{ $visitor->meetup_spot ?: 'No special instructions.' }}</td>
                    </tr>
                </table>

                <div
                    style="background:#D48626; color:#1390B4; padding:15px; border-radius:8px; margin:20px 0; font-weight:bold;">
                    Present attached QR Code at the reception desk for check-in.
                </div>

                <p style="color:#4b5563; font-size:16px;">Thanks,<br><strong>Your Team at LAIC</strong></p>
            </td>
        </tr>
    </table>

</body>

</html>
