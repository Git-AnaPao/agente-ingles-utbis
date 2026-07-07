@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-semibold text-white border-b-2 transition duration-200'
    : 'inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 border-b-2 border-transparent transition duration-200';

$style = ($active ?? false)
    ? 'border-color: var(--color-warning); background-color: rgba(255,255,255,0.1); font-family:Inter,sans-serif;'
    : 'font-family:Inter,sans-serif;';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'style' => $style]) }}>
    {{ $slot }}
</a>
