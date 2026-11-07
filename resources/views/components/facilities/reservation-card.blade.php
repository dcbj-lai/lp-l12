@props(['title', 'metadata', 'recurring' => false])
<details wire:ignore.self {{ $attributes->class(['reservation-card']) }}>
    <summary class="reservation-card-summary">
        <span class="reservation-card-title">{{ $title }} @if($recurring)<span class="reservation-card-recurring">Recurring</span>@endif</span>
        <span class="reservation-card-badges">{{ $badges ?? '' }}</span>
        <span class="reservation-card-metadata">{{ $metadata }}</span>
        <span class="reservation-card-chevron" aria-hidden="true"><x-facilities.icon name="chevron-down" /></span>
        <span class="sr-only">Reservation details</span>
    </summary>
    <div class="reservation-card-body relative space-y-3">{{ $slot }}</div>
</details>
