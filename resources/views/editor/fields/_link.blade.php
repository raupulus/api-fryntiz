{{--
    Tarjeta de enlace sin título, descripción ni imagen (la web enlazada no se
    dejó leer): un enlace normal con la dirección.
--}}
@php
    $safeLink = \App\Services\Content\ContentBlockValidator::isSafeUrl($link, false) ? $link : '#';
@endphp
<p id="{{$id}}" class="r-web-preview-simple">
    <a class="r-web-preview-simple-link" target="_blank" rel="nofollow noindex noopener noreferrer" href="{{ $safeLink }}">{{ $safeLink !== '#' ? preg_replace('/https*:\/\//', '', $safeLink) : '' }}</a>
</p>
