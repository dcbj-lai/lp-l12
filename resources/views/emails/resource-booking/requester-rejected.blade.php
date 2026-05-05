<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:20px;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:8px;border:1px solid #e4e4e7;overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background:#dc2626;color:white;padding:16px 20px;font-weight:bold;">
                            Booking Rejected
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:20px;font-size:14px;color:#18181b;">

                            <p style="margin:0 0 12px;">
                                Your booking request has been <strong>rejected</strong>.
                            </p>

                            <p><strong>Title:</strong><br>{{ $reservation->title }}</p>

                            <p>
                                <strong>Schedule:</strong><br>
                                {{ \Carbon\Carbon::parse($reservation->start_datetime)->format('M d, Y h:i A') }}
                                →
                                {{ \Carbon\Carbon::parse($reservation->end_datetime)->format('M d, Y h:i A') }}
                            </p>

                            @if ($reservation->resource)
                                <p><strong>Room:</strong><br>{{ $reservation->resource->name }}</p>
                            @endif

                            @if ($reservation->equipment->count())
                                <p><strong>Equipment:</strong></p>
                                <ul>
                                    @foreach ($reservation->equipment as $item)
                                        <li>{{ $item->name }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div
                                style="margin-top:16px;padding:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;">
                                <strong style="color:#991b1b;">Reason:</strong>
                                <p style="margin:6px 0 0;">
                                    {{ $reservation->approval_note }}
                                </p>
                            </div>

                            <p style="margin-top:16px;">
                                Status: <strong style="color:#dc2626;">REJECTED</strong>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="padding:12px;text-align:center;font-size:12px;color:#71717a;border-top:1px solid #e4e4e7;">
                            Life Portal • Resource Booking
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
