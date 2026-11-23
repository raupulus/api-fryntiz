@props([
    'align' => 'right',
    'width' => '48',
    'contentClasses' => 'py-1.5 bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl rounded-2xl',
])

@php
    $alignmentClasses = match ($align) {
        'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
        'top' => 'origin-top',
        'none' => '',
        default => 'ltr:origin-top-right rtl:origin-top-left end-0',
    };

    $widthClass = match ($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
        default => $width,
    };
@endphp

<div class="relative inline-block text-start"
     x-data="{ open: false }"
     @click.outside="open = false"
     @close.stop="open = false"
     @keydown.escape.window="open = false">
    <button type="button"
            @click="open = ! open"
            :aria-expanded="open.toString()"
            aria-haspopup="menu"
            class="inline-flex items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-lg">
        {{ $trigger }}
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="menu"
        class="absolute z-50 mt-2 {{ $widthClass }} rounded-2xl shadow-lg {{ $alignmentClasses }}"
        @click="open = false"
    >
        <div class="{{ $contentClasses }}">
            {{ $slot }}
        </div>
    </div>
</div>
