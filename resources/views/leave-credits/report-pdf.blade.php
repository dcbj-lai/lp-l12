<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
            font-size: 10px;
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
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .number {
            text-align: right;
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
        <h1>Leave Credits</h1>
        <p class="meta">Employees: {{ $users->count() }}</p>
        <p class="meta">Generated {{ $generatedAt->format('Y-m-d H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Email</th>
                <th>Department</th>
                <th>Position</th>
                <th class="number">Leave</th>
                <th class="number">WFH</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $user->preferred_name ?: $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->department?->name ?? '-' }}</td>
                    <td>{{ $user->position ?? '-' }}</td>
                    <td class="number">{{ number_format((float) ($user->requestCredit?->pto ?? 0), 2) }}</td>
                    <td class="number">{{ number_format((float) ($user->requestCredit?->wfh ?? 0), 2) }}</td>
                    <td>{{ optional($user->requestCredit?->updated_at)->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="8">No employees found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated {{ $generatedAt->format('Y-m-d H:i') }}
    </div>
</body>

</html>
