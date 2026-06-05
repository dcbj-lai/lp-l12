<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            color: #222;
            font-family: DejaVu Sans, sans-serif;
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
            border-collapse: collapse;
            width: 100%;
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

        .url {
            word-break: break-all;
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
        <h1>Users</h1>
        <p class="meta">Users: {{ $users->count() }}</p>
        @if ($search !== '')
            <p class="meta">Search: {{ $search }}</p>
        @endif
        <p class="meta">Generated {{ $generatedAt->format('Y-m-d H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Preferred Name</th>
                <th>Vcard URL</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->preferred_name ?? '-' }}</td>
                    <td class="url">{{ $user->cardUrl() }}</td>
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="5">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated {{ $generatedAt->format('Y-m-d H:i') }}
    </div>
</body>

</html>
