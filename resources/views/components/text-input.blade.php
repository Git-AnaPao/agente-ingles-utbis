@props(['disabled' => false, 'invalid' => false])

<input
    @disabled($disabled)
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->merge([
        'class' => 'block w-full rounded-2xl border px-4 py-3 text-sm outline-none transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500',
        'style' => 'background-color: var(--color-card); border-color: var(--color-control-border); color: var(--color-text);',
    ]) }}>
