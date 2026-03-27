@props(['variant' => 'default'])

@php
    $base = '
    relative rounded-xl border min-h-[150px] overflow-hidden
    transition-all duration-300 ease-in-out
    bg-white text-gray-900 border-gray-200
    dark:bg-zinc-800 dark:text-gray-200 dark:border-zinc-700
';

    $variants = [
        'default' => '
        shadow-md
        hover:-translate-y-[2px]
        hover:shadow-lg
    ',

        'danger' => '
        shadow-[0_0_8px_rgba(239,68,68,0.2)]
        hover:-translate-y-[2px]
        hover:shadow-[0_0_22px_rgba(239,68,68,0.45)]
        border-red-400/40
    ',

        'soft-danger' => '
        shadow-[0_0_6px_rgba(239,68,68,0.12)]
        hover:-translate-y-[2px]
        hover:shadow-[0_0_16px_rgba(239,68,68,0.3)]
    ',
    ];

    $variantClass = $variants[$variant] ?? $variants['default'];
@endphp

<div {{ $attributes->merge([
    'class' => "$base $variantClass",
]) }}>
    {{ $slot }}
</div>
