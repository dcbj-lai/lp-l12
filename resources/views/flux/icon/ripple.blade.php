{{-- Credit: Lucide (https://lucide.dev) --}}

@props([
    'variant' => 'outline',
])

@php
if ($variant === 'solid') {
    throw new \Exception('The "solid" variant is not supported in Lucide.');
}

$classes = Flux::classes('shrink-0')
    ->add(match($variant) {
        'outline' => '[:where(&)]:size-6',
        'solid' => '[:where(&)]:size-6',
        'mini' => '[:where(&)]:size-5',
        'micro' => '[:where(&)]:size-4',
    });

$strokeWidth = match ($variant) {
    'outline' => 2,
    'mini' => 2.25,
    'micro' => 2.5,
};
@endphp

<svg {{ $attributes->merge(['class' => 'w-6 h-6 text-gray-500 dark:text-gray-300']) }}
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round">
   <path d="M3 12c0-4.97 4.03-9 9-9s9 4.03 9 9-4.03 9-9 9c-1.5 0-2.93-.36-4.2-1l-3.8 1 1-3.8C3.36 14.93 3 13.5 3 12z"/>
   <path d="M8 10c1.5 1 3.5 1 5 0" />
   <path d="M8 14c1.5 1 3.5 1 5 0" />
</svg>


