@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'block w-full rounded-xl border px-4 py-3 text-sm outline-none transition duration-200 focus:ring-4 focus:ring-[var(--color-primary)]/15 disabled:opacity-60 disabled:cursor-not-allowed',
        'style' => 'font-family: Inter, sans-serif; background-color: var(--color-bg); border-color: var(--color-border); color: var(--color-text);',
    ]) }}>
