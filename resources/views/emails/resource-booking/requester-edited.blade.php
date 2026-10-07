<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;color:#18181b;line-height:1.5">
    <h2>Reservation details updated</h2>
    <p>An administrator updated <strong>{{ $reservation->title }}</strong>. Your complete reservation details are below. Changed details are highlighted in yellow.</p>
    @php
        $changedDetails = collect($changes)->keyBy('label');
        $details = [
            'Reservation number' => '#' . $reservation->id,
            'Event name' => $reservation->title,
            'Requester name' => $reservation->requester_name,
            'Requester email' => $reservation->requester_email,
            'Rooms' => $reservation->room_names,
            'Start' => $reservation->start_datetime?->format('M j, Y g:i A'),
            'End' => $reservation->end_datetime?->format('M j, Y g:i A'),
            'Recurrence' => $reservation->recurrence_label ? $reservation->recurrence_label . ' · Date ' . $reservation->recurrence_position . ' of ' . $reservation->recurrence_total : 'Does not repeat',
            'Number of pax' => $reservation->number_of_pax,
            'Setup arrangement' => $reservation->setup_arrangement,
            'Contact number' => $reservation->contact_number,
            'Equipment' => $reservation->equipment->map(fn ($item) => $item->name . ' × ' . $item->pivot->quantity)->join(', '),
            'Consumables' => $reservation->consumables,
            'Notes' => $reservation->notes,
            'Status' => ['pending' => 'For Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'][$reservation->status] ?? ucfirst($reservation->status),
            'Approval / rejection note' => $reservation->approval_note,
            'Billing status' => ucfirst($reservation->billing_status ?: 'unbilled'),
            'SOA sent' => $reservation->soa_sent_at?->format('M j, Y'),
            'Payment due' => $reservation->payment_due_at?->format('M j, Y'),
            'Date paid' => $reservation->paid_at?->format('M j, Y'),
        ];
        $attachments = ['Floor plan' => $reservation->floor_plan_path, 'Gate pass' => $reservation->gate_pass_path, 'Other attachment' => $reservation->attachment_path, 'SOA' => $reservation->soa_path, 'Payment proof' => $reservation->payment_proof_path];
    @endphp
    <table style="border-collapse:collapse;width:100%;border:1px solid #e4e4e7" cellpadding="8">
        <tbody>
            @foreach ($details as $label => $value)
                <tr style="background:{{ $changedDetails->has($label) ? '#fef3c7' : '#ffffff' }};border-bottom:1px solid #e4e4e7">
                    <th align="left" style="width:30%">{{ $label }} @if($changedDetails->has($label))<span style="color:#92400e">(Changed)</span>@endif</th>
                    <td style="white-space:pre-line">{{ $value === null || $value === '' ? 'None' : $value }}</td>
                </tr>
            @endforeach
            @foreach ($attachments as $label => $path)
                <tr style="background:{{ $changedDetails->has($label) ? '#fef3c7' : '#ffffff' }};border-bottom:1px solid #e4e4e7">
                    <th align="left">{{ $label }} @if($changedDetails->has($label))<span style="color:#92400e">(Changed)</span>@endif</th>
                    <td>@if($path)<a href="{{ Storage::disk(config('filesystems.facility_upload_disk'))->url($path) }}">View {{ strtolower($label) }}</a>@else None @endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="font-size:12px;color:#71717a">Disposables and consumables are to be provided by or charged to the organizer.</p>
    <h3>Changes made</h3>
    <table style="border-collapse:collapse;width:100%" border="1" cellpadding="8">
        <thead><tr style="background:#f4f4f5"><th align="left">Detail</th><th align="left">Before</th><th align="left">Now</th></tr></thead>
        <tbody>
            @foreach ($changes as $change)
                <tr style="background:#fef3c7"><th align="left">{{ $change['label'] }}</th><td style="white-space:pre-line">{{ $change['before'] }}</td><td style="white-space:pre-line"><strong>{{ $change['after'] }}</strong></td></tr>
            @endforeach
        </tbody>
    </table>
    <p>Current status: <strong>{{ ['pending' => 'For Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'][$reservation->status] ?? ucfirst($reservation->status) }}</strong></p>
    <p>Life Portal • Facilities Reservations</p>
</body>
</html>
