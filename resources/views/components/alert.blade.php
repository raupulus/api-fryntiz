@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
    'icon' => null,
])

@php
    $types = [
        'info' => [
            'container' => 'bg-surface-container-high text-on-surface border-tertiary-fixed',
            'icon' => 'info',
            'iconColor' => 'text-primary-container dark:text-primary',
        ],
        'success' => [
            'container' => 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-950 dark:text-emerald-100 border-emerald-300 dark:border-emerald-800',
            'icon' => 'check_circle',
            'iconColor' => 'text-emerald-600 dark:text-emerald-400',
        ],
        'warning' => [
            'container' => 'bg-amber-50 dark:bg-amber-950/30 text-amber-950 dark:text-amber-100 border-amber-300 dark:border-amber-800',
            'icon' => 'warning',
            'iconColor' => 'text-amber-600 dark:text-amber-400',
        ],
        'error' => [
            'container' => 'bg-error-container/40 dark:bg-error-container/20 text-on-surface border-error/30',
            'icon' => 'error',
            'iconColor' => 'text-error',
        ],
    ];

    $cfg = $types[$type] ?? $types['info'];
    $iconName = $icon ?? $cfg['icon'];
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition.opacity.duration.200ms
    {{ $attributes->merge(['class' => 'relative rounded-2xl border p-4 shadow-sm flex items-start gap-3.5 ' . $cfg['container']]) }}
    role="alert"
>
    <div class="shrink-0 mt-0.5 {{ $cfg['iconColor'] }}">
        <span class="material-symbols-outlined text-xl">{{ $iconName }}</span>
    </div>

    <div class="flex-1 text-sm leading-relaxed">
        @if($title)
            <h5 class="font-bold mb-1 tracking-tight">{{ $title }}</h5>
        @endif
        <div>{{ $slot }}</div>
    </div>

    @if($dismissible)
        <button
            type="button"
            @click="show = false"
            class="shrink-0 p-1 text-on-surface-variant hover:text-on-surface rounded-lg hover:bg-surface-container/50 transition-colors focus:outline-none"
            aria-label="Cerrar"
        >
            <span class="material-symbols-outlined text-base leading-none">close</span>
        </button>
    @endif
</div>
