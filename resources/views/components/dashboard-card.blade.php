@props(['variant' => 'default'])

@php
    $variants = [
        'default' => '',
        'highlight' => 'border-amber-400',
        'success' => 'border-green-400',
    ];

    $variantClass = $variants[$variant] ?? '';
@endphp

<div
    {{ $attributes->merge([
        'class' => "
            relative rounded-xl border min-h-[150px] overflow-hidden
            transition-all duration-300 ease-in-out
            shadow-md hover:shadow-lg hover:-translate-y-[2px]
            bg-white text-gray-900 border-gray-200
            dark:bg-zinc-800 dark:text-gray-200 dark:border-zinc-700
            {$variantClass}
        ",
    ]) }}>
    {{ $slot }}
</div>
