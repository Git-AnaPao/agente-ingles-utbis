@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1.5 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5 text-xs font-medium rounded-lg px-3 py-1.5"
                style="background-color: color-mix(in srgb, var(--color-warning) 20%, transparent); color: var(--color-primary); font-family:Inter,sans-serif;">
                <span aria-hidden="true">⚠️</span>
                {{ $message }}
            </li>
        @endforeach
    </ul>
@endif
