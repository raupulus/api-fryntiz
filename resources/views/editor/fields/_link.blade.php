{{--
    Tarjeta de enlace sin título, descripción ni imagen (la web enlazada no se
    dejó leer): un enlace normal con la dirección.
--}}
<p id="{{$id}}" class="r-web-preview-simple">
    <a class="r-web-preview-simple-link" target="_blank" rel="nofollow noindex noreferrer" href="{{$link}}">{{preg_replace('/https*:\/\//', '', $link)}}</a>
</p>
