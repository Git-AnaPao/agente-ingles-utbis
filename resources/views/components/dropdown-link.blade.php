@props(['as' => 'a'])

@php
    $classes = 'block w-full px-4 py-2.5 text-start text-sm leading-5 transition duration-200 ease-in-out rounded-lg hover:bg-black/5 dark:hover:bg-white/5';
    $style = 'color: var(--color-text); font-family: Inter, sans-serif;';
@endphp

@if ($as === 'button')
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes, 'style' => $style]) }}>
        {{ $slot }}
    </button>
@else
    <a {{ $attributes->merge(['class' => $classes, 'style' => $style]) }}>
        {{ $slot }}
    </a>
@endif
