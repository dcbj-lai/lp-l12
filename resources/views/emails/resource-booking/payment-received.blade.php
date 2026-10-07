<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#18181b;line-height:1.6">
<div style="max-width:620px;margin:24px auto;background:white;padding:28px;border-radius:8px">
    <p style="font-size:13px;color:#71717a">LIFE Portal · Facilities Reservations</p>
    <h2>Payment received</h2>
    <p>Hello,</p>
    <p>Thank you. Payment for your reservation has been recorded, and its payment status is now <strong>Paid</strong>.</p>
    <table style="width:100%;border-collapse:collapse" cellpadding="8">
        @foreach (['Event' => $details['title'], 'Reservation' => '#'.$details['reservation_id'], 'Event date and time' => $details['event_date'], 'Rooms' => $details['room'], 'Date paid' => $details['paid_date'], 'Payment status' => 'Paid'] as $label => $value)
            <tr style="border-bottom:1px solid #e4e4e7"><th align="left" style="width:40%">{{ $label }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>
    <p>For any questions or concerns, please contact <a href="mailto:events.facilities@life.edu.ph">events.facilities@life.edu.ph</a>.</p>
    <p>Thank you,<br>LIFE Facilities Team</p>
</div></body></html>
