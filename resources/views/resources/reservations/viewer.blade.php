<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Facilities reservations · LP</title>@vite(['resources/css/app.css'])</head>
<body class="bg-zinc-50 text-zinc-800">
<main class="mx-auto max-w-5xl p-6 space-y-5">
    <header class="flex items-center justify-between gap-4">
        <div><h1 class="text-2xl font-semibold">Facilities reservations</h1><p class="text-sm text-zinc-500">Facilities Viewer · {{ auth()->user()->name }}</p></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border bg-white px-4 py-2">Log out</button></form>
    </header>
    <nav aria-label="Reservation status" class="inline-flex rounded-lg border bg-zinc-100 p-1">
        @foreach(['pending' => 'For Approval', 'approved' => 'Approved'] as $tabStatus => $label)
            <a href="{{ route('resources.reservations.index', ['status' => $tabStatus, 'sort' => $direction]) }}" @if($status === $tabStatus) aria-current="page" @endif class="rounded-md px-4 py-2 font-medium {{ $status === $tabStatus ? 'bg-white shadow-sm' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <form method="GET" class="flex items-center gap-2"><input type="hidden" name="status" value="{{ $status }}"><label for="sort">Sort by event date</label><select id="sort" name="sort" class="rounded-md border p-2"><option value="asc" @selected($direction === 'asc')>Earliest first</option><option value="desc" @selected($direction === 'desc')>Latest first</option></select><button class="rounded-md border bg-white px-3 py-2">Apply</button></form>
    @forelse($reservations as $reservation)
        <article class="rounded-lg border bg-white p-5 shadow-sm space-y-2">
            <h2 class="font-semibold text-lg">{{ $reservation->title }}</h2>
            <p>{{ $reservation->start_datetime->format('M j, Y g:i A') }}–{{ $reservation->end_datetime->format('M j, Y g:i A') }} · {{ $reservation->room_names }}</p>
            <div><span class="rounded-md px-2 py-1 text-sm {{ $status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ $status === 'approved' ? 'Approved' : 'For Approval' }}</span>@if($reservation->recurrence_series_id)<span class="ml-2 text-sm">Recurring event · {{ $reservation->recurrence_position }} of {{ $reservation->recurrence_total }} · {{ $reservation->recurrence_label }}</span>@endif</div>
            <p>{{ $reservation->number_of_pax }} pax · {{ $reservation->setup_arrangement }} setup</p>
            @if($reservation->equipment->isNotEmpty())<p>Equipment: {{ $reservation->equipment->map(fn ($item) => $item->name . ' × ' . $item->pivot->quantity)->join(', ') }}</p>@endif
            @if($reservation->notes)<p class="whitespace-pre-line">Notes: {{ $reservation->notes }}</p>@endif
            @if($reservation->floor_plan_path)<a class="text-blue-700 underline" href="{{ route('resources.reservations.floor-plan', $reservation) }}" target="_blank" rel="noopener">Floor plan</a>@endif
            @if($reservation->recurrence_series_id)
                <details class="rounded-md border p-3">
                    <summary class="cursor-pointer font-medium">View recurring dates</summary>
                    <div class="mt-3 space-y-3">
                        @foreach($seriesDates->get($reservation->recurrence_series_id, collect()) as $date)
                            <section class="rounded-md border p-3 space-y-1">
                                <p class="font-medium">{{ $date->start_datetime->format('M j, Y g:i A') }}–{{ $date->end_datetime->format('M j, Y g:i A') }}</p>
                                <p>{{ $date->status === 'approved' ? 'Approved' : 'For Approval' }} · {{ $date->room_names }}</p>
                                <p>{{ $date->number_of_pax }} pax · {{ $date->setup_arrangement }} setup</p>
                                @if($date->equipment->isNotEmpty())<p>Equipment: {{ $date->equipment->map(fn ($item) => $item->name . ' × ' . $item->pivot->quantity)->join(', ') }}</p>@endif
                                @if($date->notes)<p class="whitespace-pre-line">Notes: {{ $date->notes }}</p>@endif
                                @if($date->floor_plan_path)<a class="text-blue-700 underline" href="{{ route('resources.reservations.floor-plan', $date) }}" target="_blank" rel="noopener">Floor plan</a>@endif
                            </section>
                        @endforeach
                    </div>
                </details>
            @endif
        </article>
    @empty<p>No {{ $status === 'approved' ? 'approved reservations' : 'reservations for approval' }}.</p>@endforelse
    {{ $reservations->links() }}
</main>
</body></html>
