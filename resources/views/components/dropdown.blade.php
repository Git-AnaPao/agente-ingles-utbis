@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1', 'id' => 'dropdown-panel'])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    '56' => 'w-56',
    default => $width,
};
@endphp

<div class="relative"
     x-data="{ open: false }"
     @click.outside="open = false"
     @close.stop="open = false"
     @keydown.escape.window="if (open) { open = false; $nextTick(() => $refs.trigger?.focus()) }">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-cloak
            id="{{ $id }}"
            :aria-hidden="(!open).toString()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-2 {{ $width }} rounded-2xl shadow-lg {{ $alignmentClasses }}"
            style="display: none;">
        <div class="rounded-2xl overflow-hidden {{ $contentClasses }}"
             style="background: var(--color-glass); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--color-glass-border);">
            {{ $content }}
        </div>
    </div>
</div>
