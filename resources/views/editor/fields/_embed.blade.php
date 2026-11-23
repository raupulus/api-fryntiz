<div id="{{$id}}" class="r-embed-container">
    <div class="r-embed-box">
        @if($caption)
            <div class="r-embed-title">
                {!! $caption !!}
            </div>
        @endif

        @php
            $safeEmbed = \App\Services\Content\ContentBlockValidator::isSafeUrl($embed, false) ? $embed : null;
        @endphp

        @if($safeEmbed)
            <div class="r-embed-box-iframe">
                <iframe class="r-embed-iframe border-0"
                        title="{{ $caption ? trim(strip_tags((string) $caption)) : 'Contenido incrustado' }}"
                        data-width="{{$width}}"
                        data-height="{{$height}}"
                        src="{{ $safeEmbed }}"
                        loading="lazy"
                        allow="autoplay; encrypted-media"
                        allowfullscreen></iframe>
            </div>
        @endif
    </div>
</div>
