<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-secondary shadow-sm disabled:opacity-50']) }}>
    {{ $slot }}
</button>
