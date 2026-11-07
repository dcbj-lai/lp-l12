@props(['name' => 'document'])
@php
    $paths = [
        'document' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M8 13h8 M8 17h5',
        'calendar' => 'M8 2v4 M16 2v4 M3 10h18 M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2',
        'lock' => 'M7 11V7a5 5 0 0 1 10 0v4 M5 11h14v10H5z M12 15v2',
        'home' => 'M3 10 12 3l9 7 M5 9v12h14V9 M9 21v-7h6v7',
        'check' => 'M20 6 9 17l-5-5',
        'clock' => 'M12 8v4l3 2 M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
        'upload' => 'M12 16V3 M7 8l5-5 5 5 M4 16v5h16v-5',
        'arrow-left' => 'M19 12H5 M12 19l-7-7 7-7',
        'arrow-right' => 'M5 12h14 M12 5l7 7-7 7',
        'chevron-down' => 'M6 9l6 6 6-6',
        'alert' => 'M12 9v4 M12 17h.01 M10.3 3.9 1.8 18.6a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0',
        'mail' => 'M3 5h18v14H3z M3 5l9 7 9-7',
    ];
@endphp
<svg {{ $attributes->class(['facility-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['document'] }}" /></svg>
