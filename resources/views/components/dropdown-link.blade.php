<a {{ $attributes->merge([
    'class' => 'block w-full px-4 py-2.5 text-start text-sm leading-5 transition duration-150 ease-in-out rounded-lg focus:outline-none',
    'style' => 'color: var(--color-primary); font-family:Inter,sans-serif;',
    'onmouseover' => "this.style.backgroundColor='var(--color-bg)'",
    'onmouseout'  => "this.style.backgroundColor='transparent'",
]) }}>
    {{ $slot }}
</a>
