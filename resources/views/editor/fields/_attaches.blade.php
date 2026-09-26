{{--
    Adjunto. Con los datos de la v1 (contenido, miniatura, icono del tipo) pinta
    lo mismo que antes; con los que devuelve la subida de la v2 (url, nombre,
    tamaño, extensión e id del fichero), el nombre y el botón de descarga.
--}}
<div id="{{$id}}"
     class="r-attaches-container"
     @if(filled($file['content_id'] ?? null)) data-content_id="{{$file['content_id']}}" @endif
     @if(filled($file['content_file_id'] ?? null)) data-content_file_id="{{$file['content_file_id']}}" @endif
     @if(filled($file['file_id'] ?? null)) data-file_id="{{$file['file_id']}}" @endif>

    <div class="r-attaches-box">
        @if ($icon !== '')
            <div class="r-attaches-img">
                <img src="{{$icon}}" alt="{{$title}}">
            </div>
        @endif

        <div class="r-attaches-info">
            <div>{{$title}}@if($name !== '' && $name !== $title) <span class="r-attaches-info-originalname"> ({{$name}})</span>@endif</div>

            @if ($size)
                <div class="r-attaches-info-size">
                    {{$size}}
                </div>
            @endif
        </div>

        <div class="r-attaches-download" data-url_download="{{$url}}">
            <a href="{{$url}}" download target="_self" class="r-attaches-download-link">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                          d="M7 10L11.8586 14.8586C11.9367 14.9367 12.0633 14.9367 12.1414 14.8586L17 10"></path>
                </svg>
            </a>
        </div>
    </div>
</div>
