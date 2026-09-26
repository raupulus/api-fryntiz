{{-- Vista previa del contenido (C7). Estilos en panel.css, sección «cp-preview». --}}
<x-filament-panels::page>
    <div class="cp-preview">
        @forelse ($this->getPreviewPages() as $page)
            <article class="cp-preview__page">
                <h2 class="cp-preview__page-title">Página {{ $page->order }} · {{ $page->title }}</h2>
                {{-- Lo guardado ya pasó por la limpieza al guardar (F3): se enseña tal cual lo sirve la API. --}}
                <div class="cp-preview__body">{!! $page->content !!}</div>
            </article>
        @empty
            <p class="cp-preview__empty">Este contenido todavía no tiene páginas.</p>
        @endforelse
    </div>
</x-filament-panels::page>
