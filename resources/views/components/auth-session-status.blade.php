@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium']) }}
         style="background-color: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary); font-family:Inter,sans-serif;"
         role="status">
        <span aria-hidden="true">✅</span>
        {{ $status }}
    </div>
@endif
