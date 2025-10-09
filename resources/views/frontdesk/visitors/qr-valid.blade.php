<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visitor Verified</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800 flex flex-col items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-lg p-6 w-full max-w-md text-center">
        <h1 class="text-2xl font-bold text-green-600 mb-4">✅ Visitor Verified</h1>

        <div class="text-left space-y-2">
            <p><strong>Name:</strong> {{ $visitor->full_name }}</p>
            <p><strong>Company:</strong> {{ $visitor->company ?? 'N/A' }}</p>
            <p><strong>Email:</strong> {{ $visitor->email }}</p>
            <p><strong>Mobile:</strong> {{ $visitor->mobile }}</p>
            <p><strong>Purpose:</strong> {{ $visitor->purpose }}</p>
            <p><strong>Visit Date:</strong> {{ \Carbon\Carbon::parse($visitor->visit_date)->format('F d, Y') }}</p>
            <p><strong>Host:</strong> {{ optional($visitor->visitedUser)->name ?? 'Unassigned' }}</p>
            <p><strong>Status:</strong>
                <span class="px-2 py-1 rounded-full text-sm 
                    @if($visitor->status === 'checked_in') bg-green-100 text-green-700
                    @elseif($visitor->status === 'approved') bg-emerald-100 text-emerald-700
                    @elseif($visitor->status === 'endorsed') bg-blue-100 text-blue-700
                    @elseif($visitor->status === 'pending') bg-yellow-100 text-yellow-700
                    @elseif($visitor->status === 'checked_out') bg-purple-100 text-purple-700
                    @else bg-gray-100 text-gray-700 @endif">
                    {{ ucfirst(str_replace('_', ' ', $visitor->status)) }}
                </span>
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-6 flex flex-col gap-2">
            @if($visitor->status === 'approved')
                {{-- Check-In button --}}
                <form action="{{ route('frontdesk.checkin', $visitor) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">
                        Check In
                    </button>
                </form>
            @endif
        </div>
    </div>
</body>

</html>
