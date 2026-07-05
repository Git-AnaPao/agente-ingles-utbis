{{--
    Estado de sesión — Style Guide §Verde Aprendizaje (#518C4F) para estados positivos
--}}
@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium']) }}
         style="background-color:#EAF5EA; color:#518C4F; font-family:Inter,sans-serif;"
         role="status">
        <span aria-hidden="true">✅</span>
        {{ $status }}
    </div>
@endif
