@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block w-full ps-4 pe-4 py-3 border-l-4 text-start text-sm font-semibold text-white transition duration-150 ease-in-out focus:outline-none'
    : 'block w-full ps-4 pe-4 py-3 border-l-4 border-transparent text-start text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 transition duration-150 ease-in-out focus:outline-none';

$style = ($active ?? false)
    ? 'border-color: var(--color-warning); background-color: rgba(255,255,255,0.1); font-family:Inter,sans-serif;'
    : 'font-family:Inter,sans-serif;';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'style' => $style]) }}>
    {{ $slot }}
</a>
