<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>New Resource Booking Request</title>
</head>

<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:20px 0;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e4e4e7;">

                    <!-- Header -->
                    <tr>
                        <td style="background:#9E1D20;color:#ffffff;padding:16px 20px;font-size:18px;font-weight:bold;">
                            New Resource Booking Request
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:20px;color:#18181b;font-size:14px;line-height:1.6;">

                            <!-- Requester -->
                            <p style="margin:0 0 12px 0;">
                                <strong>Requester Email:</strong><br>
                                {{ $reservation->requester_email }}
                            </p>

                            <!-- Title -->
                            <p style="margin:0 0 12px 0;">
                                <strong>Title:</strong><br>
                                {{ $reservation->title }}
                            </p>

                            <!-- Schedule -->
                            <p style="margin:0 0 12px 0;">
                                <strong>Schedule:</strong><br>
                                {{ \Carbon\Carbon::parse($reservation->start_datetime)->format('M d, Y h:i A') }}
                                →
                                {{ \Carbon\Carbon::parse($reservation->end_datetime)->format('M d, Y h:i A') }}
                            </p>

                            <!-- Room -->
                            @if ($reservation->resource)
                                <p style="margin:0 0 12px 0;">
                                    <strong>Room:</strong><br>
                                    {{ $reservation->resource->name }}
                                </p>
                            @endif

                            <!-- Equipment -->
                            @if ($reservation->equipment && $reservation->equipment->count())
                                <p style="margin:0 0 6px 0;">
                                    <strong>Equipment:</strong>
                                </p>

                                <ul style="margin:0 0 12px 18px;padding:0;">
                                    @foreach ($reservation->equipment as $item)
                                        <li style="margin-bottom:4px;">
                                            {{ $item->name }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <!-- Status -->
                            <p style="margin:0 0 16px 0;">
                                <strong>Status:</strong>
                                <span style="color:#9E1D20;font-weight:bold;">
                                    {{ strtoupper($reservation->status) }}
                                </span>
                            </p>

                            <!-- Divider -->
                            <hr style="border:none;border-top:1px solid #e4e4e7;margin:20px 0;">

                            <!-- CTA -->
                            <div style="text-align:center;">
                                <a href="{{ $url }}"
                                    style="display:inline-block;background:#9E1D20;color:#ffffff;text-decoration:none;
                                           padding:10px 18px;border-radius:6px;font-size:14px;font-weight:500;">
                                    View Booking Request
                                </a>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="padding:12px 20px;background:#fafafa;color:#71717a;font-size:12px;text-align:center;border-top:1px solid #e4e4e7;">
                            Life Portal • Resource Booking System
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
