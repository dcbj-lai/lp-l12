<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
            font-size: 9px;
            line-height: 1.35;
        }

        .header {
            border-bottom: 2px solid #1f2937;
            margin-bottom: 14px;
            padding-bottom: 10px;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 4px;
        }

        .meta {
            color: #555;
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 4px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .empty {
            color: #666;
            padding: 18px;
            text-align: center;
        }

        .footer {
            color: #666;
            font-size: 10px;
            margin-top: 12px;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $event->title }} Registrants</h1>
        @if ($event->start_datetime)
            <p class="meta">Event Date: {{ $event->start_datetime->format('M d, Y g:i A') }}</p>
        @endif
        @if ($event->location)
            <p class="meta">Location: {{ $event->location }}</p>
        @endif
        <p class="meta">Attending: {{ $registrations->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Position</th>
                <th>Mobile</th>
                <th>Emergency Contact</th>
                <th>Emergency Relationship</th>
                <th>Emergency Phone</th>
                <th>Dietary Preference</th>
                <th>Allergies / Medical Notes</th>
                <th>Guests</th>
                @foreach ($customFieldLabels as $label)
                    <th>{{ $label }}</th>
                @endforeach
                <th>Responded At</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registrations as $index => $registration)
                @php($user = $registration->user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $user?->preferred_name ?: $user?->name ?: '-' }}</td>
                    <td>{{ $user?->email ?? '-' }}</td>
                    <td>{{ $user?->department?->name ?? '-' }}</td>
                    <td>{{ $user?->position ?? '-' }}</td>
                    <td>{{ $user?->phone_mobile ?? '-' }}</td>
                    <td>{{ $user?->emergency_contact_name ?? '-' }}</td>
                    <td>{{ $user?->emergency_contact_relationship ?? '-' }}</td>
                    <td>{{ $user?->emergency_contact_phone ?? '-' }}</td>
                    <td>{{ $user?->dietary_preference ?? '-' }}</td>
                    <td>{{ $user?->medical_notes ?? '-' }}</td>
                    <td>{{ $registration->guest_count }}</td>
                    @foreach ($customFieldLabels as $fieldIndex => $label)
                        <td>{{ $registration->customFieldAnswer((int) $fieldIndex) ?: '-' }}</td>
                    @endforeach
                    <td>{{ optional($registration->responded_at)->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ 13 + count($customFieldLabels) }}">No attending registrants yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated {{ $generatedAt->format('Y-m-d H:i') }}
    </div>
</body>

</html>
