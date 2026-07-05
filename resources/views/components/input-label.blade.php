{{--
    Etiqueta de campo — Style Guide §Tipografía (Inter Medium)
    Color Verde Oscuro para dar jerarquía visual.
--}}
@props(['value'])

<label {{ $attributes->merge([
    'class' => 'block text-sm font-medium mb-1.5',
    'style' => 'font-family: Inter, sans-serif; color:#27594B;',
]) }}>
    {{ $value ?? $slot }}
</label>
