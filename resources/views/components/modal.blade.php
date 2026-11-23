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
    $titleId = 'modal-title-' . \Illuminate\Support\Str::slug($name);
@endphp

<div
    x-data="{
        show: false,
        previousFocus: null,
        open() {
            this.previousFocus = document.activeElement;
            this.show = true;
            this.$nextTick(() => {
                const focusable = this.$refs.dialog?.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex=&quot;-1&quot;])');
                if (focusable) focusable.focus();
            });
        },
        close() {
            this.show = false;
            this.$nextTick(() => {
                if (this.previousFocus && typeof this.previousFocus.focus === 'function') {
                    this.previousFocus.focus();
                }
            });
        },
        trapFocus(event) {
            if (!this.show || event.key !== 'Tab' || !this.$refs.dialog) return;
            const focusables = [...this.$refs.dialog.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=&quot;-1&quot;])')];
            if (!focusables.length) return;
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? open() : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? close() : null"
    x-on:keydown.escape.window="if (show) close()"
    x-on:keydown.window="trapFocus($event)"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-4 sm:py-6 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
    @if($title) aria-labelledby="{{ $titleId }}" @endif
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
        @click="close()"
    ></div>

    {{-- Contenedor del diálogo --}}
    <div
        x-ref="dialog"
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full {{ $maxWidthClasses }} max-h-[90dvh] flex flex-col rounded-3xl bg-surface-container-lowest text-on-surface shadow-2xl overflow-hidden border border-outline-variant/30 z-10 my-auto"
        @click.stop
    >
        @if($title)
            <div class="shrink-0 px-6 py-4 border-b border-outline-variant/20 flex items-center justify-between gap-4">
                <h3 id="{{ $titleId }}" class="text-lg font-bold text-on-surface tracking-tight">{{ $title }}</h3>
                <button
                    type="button"
                    @click="close()"
                    class="min-w-11 min-h-11 inline-flex items-center justify-center text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    aria-label="Cerrar modal"
                >
                    <span class="material-symbols-outlined text-lg leading-none" aria-hidden="true">close</span>
                </button>
            </div>
        @endif

        <div class="p-6 overflow-y-auto flex-1">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <div class="shrink-0 px-6 py-4 bg-surface-container-low/50 border-t border-outline-variant/20 flex justify-end gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
