<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#18181b;line-height:1.6">
<div style="max-width:620px;margin:24px auto;background:white;padding:28px;border-radius:8px">
    <p style="font-size:13px;color:#71717a">LIFE Portal · Facilities Reservations</p>
    <h2>{{ match($kind) {'soa' => 'Your Statement of Account', 'updated_soa' => 'Your updated Statement of Account', 'overdue' => 'Payment overdue', 'due_today' => 'Payment due today', default => 'Payment reminder'} }}</h2>
    <p>Hello,</p>
    @if ($kind === 'soa')
        <p>Please find the Statement of Account attached for your reservation. Payment is due on the date shown below, 15 days after the SOA was uploaded.</p>
    @elseif ($kind === 'updated_soa')
        <p>Please use the updated Statement of Account attached to this email. It replaces the previously issued SOA.</p>
        <p>The payment due date has been updated based on the latest SOA upload. Please refer to the date below.</p>
    @elseif ($kind === 'overdue')
        <p>Our records show that payment for this reservation is overdue. Please arrange payment using the instructions in the attached Statement of Account.</p>
    @elseif ($kind === 'due_today')
        <p>This is a reminder that payment for your reservation is due today. The Statement of Account is attached for your reference.</p>
    @else
        <p>This is a reminder about the upcoming payment due date for your reservation. The Statement of Account is attached for your reference.</p>
    @endif
    <table style="width:100%;border-collapse:collapse" cellpadding="8">
        @foreach (['Event' => $details['title'], 'Reservation' => '#' . $details['reservation_id'], 'Event date and time' => $details['event_date'], 'Room' => $details['room'], 'Payment due date' => $details['due_date'], 'Payment status' => $details['paid'] ? 'Paid' : 'Awaiting payment'] as $label => $value)
            <tr style="border-bottom:1px solid #e4e4e7"><th align="left" style="width:40%">{{ $label }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>
    @if (!in_array($kind, ['soa', 'updated_soa'])) <p>If you have already paid, please contact the Facilities team with your proof of payment so we can update our records.</p> @endif
    <p>Payment instructions are indicated in the attached Statement of Account (SOA).</p>
    <p>For any questions or concerns, please contact <a href="mailto:events.facilities@life.edu.ph">events.facilities@life.edu.ph</a>.</p>
    <p>Thank you,<br>LIFE Facilities Team</p>
</div></body></html>
