@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
    'icon' => null,
])

@php
    $types = [
        'info' => [
            'container' => 'bg-info-container text-on-info-container border-on-info-container/20',
            'icon' => 'info',
            'iconColor' => 'text-on-info-container',
        ],
        'success' => [
            'container' => 'bg-success-container text-on-success-container border-on-success-container/20',
            'icon' => 'check_circle',
            'iconColor' => 'text-on-success-container',
        ],
        'warning' => [
            'container' => 'bg-warning-container text-on-warning-container border-on-warning-container/20',
            'icon' => 'warning',
            'iconColor' => 'text-on-warning-container',
        ],
        'error' => [
            'container' => 'bg-error-container text-on-error-container border-error/30',
            'icon' => 'error',
            'iconColor' => 'text-on-error-container',
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
        <span class="material-symbols-outlined text-xl" aria-hidden="true">{{ $iconName }}</span>
    </div>

    <div class="flex-1 text-sm leading-relaxed">
        @if($title)
            <p class="font-bold mb-1 tracking-tight">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>

    @if($dismissible)
        <button
            type="button"
            @click="show = false"
            class="shrink-0 min-w-9 min-h-9 inline-flex items-center justify-center opacity-80 hover:opacity-100 rounded-lg hover:bg-black/10 dark:hover:bg-white/10 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            aria-label="Cerrar aviso"
        >
            <span class="material-symbols-outlined text-base leading-none" aria-hidden="true">close</span>
        </button>
    @endif
</div>
