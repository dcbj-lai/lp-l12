<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;color:#18181b;line-height:1.5">
    <h2>Reservation details updated</h2>
    <p>An administrator updated <strong>{{ $reservation->title }}</strong>. The changes are listed below.</p>
    <table style="border-collapse:collapse;width:100%" border="1" cellpadding="8">
        <thead><tr style="background:#f4f4f5"><th align="left">Detail</th><th align="left">Before</th><th align="left">Now</th></tr></thead>
        <tbody>
            @foreach ($changes as $change)
                <tr><th align="left">{{ $change['label'] }}</th><td>{{ $change['before'] }}</td><td><strong>{{ $change['after'] }}</strong></td></tr>
            @endforeach
        </tbody>
    </table>
    <p>Current status: <strong>{{ ['pending' => 'For Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'][$reservation->status] ?? ucfirst($reservation->status) }}</strong></p>
    <p>Life Portal • Facilities Reservations</p>
</body>
</html>
