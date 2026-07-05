{{--
    Input de texto — Style Guide §Colores de Interfaz
    Borde neutro, focus en Verde Oscuro (#27594B).
--}}
@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'block w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none transition duration-150 focus:border-[#27594B] focus:bg-white focus:ring-4 focus:ring-[#27594B]/20 disabled:opacity-60 disabled:cursor-not-allowed',
        'style' => 'font-family: Inter, sans-serif;',
    ]) }}>
