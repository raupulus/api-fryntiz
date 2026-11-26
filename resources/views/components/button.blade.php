@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-bold tracking-tight rounded-full transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed select-none';

    $variants = [
        'primary' => 'bg-primary-container text-on-primary hover:bg-primary-container/90 focus-visible:ring-primary-container shadow-sm hover:shadow active:scale-[0.98]',
        'secondary' => 'bg-secondary-container text-on-secondary-container hover:bg-secondary-container/85 focus-visible:ring-secondary-container active:scale-[0.98]',
        'danger' => 'bg-error text-on-error hover:bg-error/90 focus-visible:ring-error shadow-sm hover:shadow active:scale-[0.98]',
        'success' => 'bg-success text-on-success hover:bg-success/90 focus-visible:ring-success shadow-sm active:scale-[0.98]',
        'surface' => 'bg-surface-container text-on-surface hover:bg-surface-container-high focus-visible:ring-outline active:scale-[0.98]',
        'hero' => 'bg-on-hero text-hero-surface hover:bg-on-hero/90 focus-visible:ring-on-hero shadow-md hover:shadow-lg active:scale-[0.98]',
        'outline' => 'border border-outline bg-transparent text-on-surface hover:bg-surface-container-low focus-visible:ring-outline active:scale-[0.98]',
        'ghost' => 'bg-transparent text-on-surface hover:bg-surface-container hover:text-on-surface focus-visible:ring-primary',
    ];

    $sizes = [
        'sm' => 'text-xs px-3.5 py-2 gap-1.5 min-h-9',
        'md' => 'text-sm px-5 py-2.5 gap-2 min-h-10',
        'lg' => 'text-base px-7 py-3.5 gap-2.5 min-h-11',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="material-symbols-outlined text-[1.25em] leading-none" aria-hidden="true">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="material-symbols-outlined text-[1.25em] leading-none" aria-hidden="true">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </button>
@endif
