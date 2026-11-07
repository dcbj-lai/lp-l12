@props(['reservation'])
@if($reservation->floor_plan_path)
    @php $extension = strtolower(pathinfo($reservation->floor_plan_path, PATHINFO_EXTENSION)); @endphp
    <button type="button" class="facility-document-row" data-floor-plan-preview
        data-url="{{ route('resources.reservations.floor-plan', $reservation) }}"
        data-type="{{ $extension === 'pdf' ? 'pdf' : (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image' : 'download') }}"
        data-title="{{ $reservation->title }}" aria-label="View floor plan for {{ $reservation->title }}">
        <x-facilities.icon name="document" /><span>Floor plan</span><span class="ml-auto text-xs text-zinc-500">View</span><x-facilities.icon name="arrow-right" />
    </button>
@endif
