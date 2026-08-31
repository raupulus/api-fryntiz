@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-bold tracking-tight rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed select-none';

    $variants = [
        'primary' => 'bg-primary-container text-on-primary hover:bg-primary-container/90 focus:ring-primary-container shadow-sm hover:shadow active:scale-[0.98]',
        'secondary' => 'bg-secondary-container text-on-surface hover:bg-secondary-container/80 focus:ring-secondary-container active:scale-[0.98]',
        'danger' => 'bg-error text-white hover:bg-error/90 focus:ring-error shadow-sm hover:shadow active:scale-[0.98]',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500 shadow-sm active:scale-[0.98]',
        'outline' => 'border border-outline-variant bg-transparent text-on-surface hover:bg-surface-container-low focus:ring-outline active:scale-[0.98]',
        'ghost' => 'bg-transparent text-on-surface hover:bg-surface-container hover:text-on-surface focus:ring-primary',
    ];

    $sizes = [
        'sm' => 'text-xs px-3 py-1.5 gap-1.5',
        'md' => 'text-sm px-5 py-2.5 gap-2',
        'lg' => 'text-base px-7 py-3.5 gap-2.5',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="material-symbols-outlined text-[1.25em] leading-none">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="material-symbols-outlined text-[1.25em] leading-none">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </button>
@endif
