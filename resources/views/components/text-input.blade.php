@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'block w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm text-gray-900 outline-none transition duration-150 focus:border-[var(--color-primary)] focus:bg-white focus:ring-4 focus:ring-[var(--color-primary)]/20 disabled:opacity-60 disabled:cursor-not-allowed',
        'style' => 'font-family: Inter, sans-serif; background-color: var(--color-bg); border-color: var(--color-border);',
    ]) }}>
