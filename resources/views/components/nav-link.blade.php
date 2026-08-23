@props(['active' => false])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center px-3 py-2 rounded-xl text-sm font-semibold text-white transition duration-200'
    : 'inline-flex items-center px-3 py-2 rounded-xl text-sm font-medium transition duration-200';

$style = ($active ?? false)
    ? 'background-color: var(--color-action); font-family: Inter, sans-serif;'
    : 'color: var(--color-text-secondary); font-family: Inter, sans-serif;';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'style' => $style, 'aria-current' => $active ? 'page' : null]) }}>
    {{ $slot }}
</a>
