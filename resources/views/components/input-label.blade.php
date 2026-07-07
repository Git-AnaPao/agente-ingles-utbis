@props(['value'])

<label {{ $attributes->merge([
    'class' => 'block text-sm font-medium mb-1.5',
    'style' => 'font-family: Inter, sans-serif; color: var(--color-text);',
] )}}>
    {{ $value ?? $slot }}
</label>
