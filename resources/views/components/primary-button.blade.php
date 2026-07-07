<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center justify-center px-5 py-2.5 rounded-2xl font-display font-bold text-sm text-white shadow-md transition duration-150 ease-in-out focus:outline-none focus:ring-4 focus:ring-orange-300 active:scale-95',
    'style' => 'background-color: var(--color-highlight);',
] )}}>
    {{ $slot }}
</button>
