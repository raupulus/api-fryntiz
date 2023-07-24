{{-- TODO: El thumbnail quizás debería añadires a los metadatos según el tipo de archivo, que sea un icono del formato --}}

<div id="{{$id}}">
    <div>
        Imagen/Icono
    </div>

    <div>
        <div>{{$title}} ({{$file['name']}})</div>
        <div>
            {{$file['size']}}
        </div>
    </div>
    <div>
        <a href="{{$file['url']}}" target="_blank" download>
            Icono Descargar
        </a>
    </div>
</div>
