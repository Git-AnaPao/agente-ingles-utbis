<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-11 items-center justify-center gap-2 px-5 py-2.5 rounded-2xl font-bold text-sm text-white transition-all duration-200 shadow-sm disabled:opacity-50', 'style' => 'background: linear-gradient(135deg, #EF4444, #DC2626); box-shadow: 0 4px 14px -2px rgba(220, 38, 38, 0.35);', 'data-loading-text' => 'Procesando...']) }}>
    {{ $slot }}
</button>
