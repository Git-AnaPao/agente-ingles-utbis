@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'titleId' => null,
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    x-data="{
        show: @js($show),
        previouslyFocused: null,
        focusables() {
            const selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                .filter(el => !el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true')
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() {
            const items = this.focusables()
            return items.length ? (items.indexOf(document.activeElement) + 1) % items.length : 0
        },
        prevFocusableIndex() {
            const items = this.focusables()
            return items.length ? (items.indexOf(document.activeElement) - 1 + items.length) % items.length : 0
        },
        close() { this.show = false },
    }"
    x-init="if (show) {
        previouslyFocused = document.activeElement;
        document.body.classList.add('overflow-y-hidden');
        {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable()?.focus(), 100);' : '' }}
    }
    $watch('show', value => {
        if (value) {
            previouslyFocused = document.activeElement;
            document.body.classList.add('overflow-y-hidden');
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable()?.focus(), 100)' : '' }}
        } else {
            document.body.classList.remove('overflow-y-hidden');
            $nextTick(() => previouslyFocused?.focus());
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="close()"
    x-on:keydown.escape.window="if (show) close()"
    x-on:keydown.tab.prevent="if (focusables().length) { $event.shiftKey ? prevFocusable().focus() : nextFocusable().focus() }"
    x-show="show"
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
    style="display: {{ $show ? 'block' : 'none' }};"
    role="dialog"
    aria-modal="true"
    @if ($titleId) aria-labelledby="{{ $titleId }}" @endif
>
    <div
        x-show="show"
        class="fixed inset-0 transform transition-all"
        x-on:click="close()"
        aria-hidden="true"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0" style="background: var(--color-text); opacity: 0.5;"></div>
    </div>

    <div
        x-show="show"
        class="mb-6 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto"
        style="background: var(--color-card);"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>
