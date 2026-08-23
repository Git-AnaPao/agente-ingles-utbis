<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'btn-primary',
    'data-loading-text' => 'Procesando...',
] )}}>
    {{ $slot }}
</button>
