@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium']) }}
         style="background-color: var(--color-success-surface); color: var(--color-success-text); font-family: Inter, sans-serif;"
         role="status" aria-live="polite">
        <span aria-hidden="true">✓</span>
        {{ $status }}
    </div>
@endif
