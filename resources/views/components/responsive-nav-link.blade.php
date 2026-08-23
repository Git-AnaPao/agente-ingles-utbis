@props(['active' => false, 'as' => 'a'])

@php
$classes = ($active ?? false)
    ? 'block w-full ps-4 pe-4 py-3 border-l-4 text-start text-sm font-semibold transition duration-150 ease-in-out'
    : 'block w-full ps-4 pe-4 py-3 border-l-4 border-transparent text-start text-sm font-medium transition duration-150 ease-in-out hover:bg-black/5 dark:hover:bg-white/5';

$style = ($active ?? false)
    ? 'border-color: var(--color-primary); background-color: color-mix(in srgb, var(--color-primary) 10%, transparent); color: var(--color-primary); font-family: Inter, sans-serif;'
    : 'color: var(--color-text-secondary); font-family: Inter, sans-serif;';
@endphp

@if ($as === 'button')
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes, 'style' => $style]) }}>
        {{ $slot }}
    </button>
@else
    <a {{ $attributes->merge(['class' => $classes, 'style' => $style, 'aria-current' => $active ? 'page' : null]) }}>
        {{ $slot }}
    </a>
@endif
