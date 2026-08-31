@props([
    'name',
    'title' => null,
    'maxWidth' => 'lg',
])

@php
    $maxWidthClasses = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
    ][$maxWidth] ?? 'sm:max-w-lg';
@endphp

<div
    x-data="{ show: false }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    style="display: none;"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop con blur --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        @click="show = false"
    ></div>

    {{-- Contenedor del diálogo --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full {{ $maxWidthClasses }} rounded-3xl bg-surface-container-lowest text-on-surface shadow-2xl overflow-hidden border border-outline-variant/30 z-10 my-8"
        @click.stop
    >
        @if($title)
            <div class="px-6 py-5 border-b border-outline-variant/20 flex items-center justify-between">
                <h3 class="text-lg font-bold text-on-surface tracking-tight">{{ $title }}</h3>
                <button
                    type="button"
                    @click="show = false"
                    class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-surface-container transition-colors focus:outline-none"
                    aria-label="Cerrar modal"
                >
                    <span class="material-symbols-outlined text-lg leading-none">close</span>
                </button>
            </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <div class="px-6 py-4 bg-surface-container-low/50 border-t border-outline-variant/20 flex justify-end gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
