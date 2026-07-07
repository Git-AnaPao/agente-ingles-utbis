<a {{ $attributes->merge([
    'class' => 'block w-full px-4 py-2.5 text-start text-sm leading-5 transition duration-200 ease-in-out rounded-lg focus:outline-none hover:bg-black/5 dark:hover:bg-white/5',
    'style' => 'color: var(--color-text); font-family:Inter,sans-serif;',
]) }}>
    {{ $slot }}
</a>
