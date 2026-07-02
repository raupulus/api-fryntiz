{{--
    Estilos en línea a propósito: el panel Admin no compila un tema Tailwind
    custom (usa el CSS por defecto de Filament), así que las clases utility
    de esta vista no se generarían en ningún build. Ver docs/info/content.md
    (sección Galerías) para el porqué.
--}}
<style>
    .gallery-preview-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 2.5rem 0;
        text-align: center;
        color: rgb(107 114 128);
    }
    .gallery-preview-empty svg {
        width: 2.5rem;
        height: 2.5rem;
        color: rgb(209 213 219);
    }
    .gallery-preview-grid {
        max-height: 65vh;
        overflow-y: auto;
        padding: 0.25rem;
    }
    .gallery-preview-grid-inner {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
        gap: 1rem;
    }
    .gallery-preview-item {
        display: block;
        aspect-ratio: 1 / 1;
        overflow: hidden;
        border-radius: 0.75rem;
        background: rgb(243 244 246);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .gallery-preview-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.18);
    }
    .gallery-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.2s ease;
    }
    .gallery-preview-item:hover img {
        transform: scale(1.06);
    }
    html.dark .gallery-preview-item {
        background: rgb(31 41 55);
    }
</style>

@if ($images->isEmpty())
    <div class="gallery-preview-empty">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
        </svg>
        <p>Esta galería todavía no tiene imágenes.</p>
    </div>
@else
    <div class="gallery-preview-grid">
        <div class="gallery-preview-grid-inner">
            @foreach ($images as $galleryImage)
                @php $file = $galleryImage->image; @endphp

                @if ($file)
                    <a
                        href="{{ $file->url }}"
                        target="_blank"
                        rel="noopener"
                        title="{{ $file->title }}"
                        class="gallery-preview-item"
                    >
                        <img src="{{ $file->thumbnail('small') }}" alt="{{ $file->alt }}" loading="lazy" />
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif
