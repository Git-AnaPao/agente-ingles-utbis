{{--
    Error de campo — Style Guide §Tarjetas de Feedback
    Sin color rojo punitivo. Tono constructivo en Amarillo Entusiasmo.
--}}
@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1.5 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5 text-xs font-medium rounded-lg px-3 py-1.5"
                style="background-color:#FFF8E8; color:#92670A; font-family:Inter,sans-serif;">
                <span aria-hidden="true">⚠️</span>
                {{ $message }}
            </li>
        @endforeach
    </ul>
@endif
