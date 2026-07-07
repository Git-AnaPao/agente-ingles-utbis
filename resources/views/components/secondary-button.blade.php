<button {{ $attributes->merge(['type' => 'button', 'class' => 'solid-card inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-xs uppercase tracking-widest shadow-sm transition duration-200 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
