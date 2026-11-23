@php
    $safeLink = \App\Services\Content\ContentBlockValidator::isSafeUrl($link, false) ? $link : '#';
    $safeImage = $image && \App\Services\Content\ContentBlockValidator::isSafeUrl($image, true) ? $image : null;
@endphp
<div id="{{$id}}" class="r-web-preview-container">
    <div class="r-web-preview-box">
        <a class="r-web-preview-link"
           target="_blank"
           rel="nofollow noindex noopener noreferrer"
           href="{{ $safeLink }}">

            @if($safeImage)
                <img src="{{ $safeImage }}" alt="" loading="lazy" decoding="async" class="r-web-preview-image object-cover">
            @endif

            <div class="r-web-preview-title">
                {!! $title !!}
            </div>

            @if($description)
                <div class="r-web-preview-description">
                    {!! $description !!}
                </div>
            @endif

            <span class="r-web-preview-anchor">{{ $safeLink !== '#' ? preg_replace('/https*:\/\//', '', $safeLink) : '' }}</span>
        </a>
    </div>
</div>
