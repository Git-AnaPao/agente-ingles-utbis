@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1.5 space-y-1']) }} role="alert" aria-live="polite">
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5 text-sm font-medium rounded-lg px-3 py-2"
                style="background-color: var(--color-error-surface); color: var(--color-error-text); font-family: Inter, sans-serif;">
                <span aria-hidden="true">!</span>
                {{ $message }}
            </li>
        @endforeach
    </ul>
@endif
