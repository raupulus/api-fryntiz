@if ($images->isEmpty())
    <div class="gl-preview__empty">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
        </svg>
        <p>Esta galería todavía no tiene imágenes.</p>
    </div>
@else
    <div class="gl-preview__grid">
        <div class="gl-preview__grid-inner">
            @foreach ($images as $galleryImage)
                @php $file = $galleryImage->image; @endphp

                @if ($file)
                    <a
                        href="{{ $file->url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="{{ $file->title }}"
                        class="gl-preview__item"
                    >
                        <img src="{{ $file->thumbnail('small') }}" alt="{{ $file->alt }}" loading="lazy" />
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif
