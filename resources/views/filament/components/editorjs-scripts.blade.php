{{--
    Editor.js y el componente Alpine "editorJsField".

    Se inyectan mediante render hook del panel (ver AdminPanelProvider) porque
    los formularios de los modales se montan por Livewire tras la carga de la
    página: un @push desde la vista del campo llegaría tarde y se descartaría.

    Con él, el componente de la pantalla de páginas (content-pages.js:
    autoguardado, aviso al salir, recorte de imágenes).

    Todo (núcleo, herramientas, traducción y componente) va empaquetado por
    Vite en resources/js/filament/editorjs.js, con las versiones fijadas en
    package.json. Las rutas de subida las pone cada campo: dependen del
    contenido (ver EditorJsField::getEndpoints()).
--}}
@vite(['resources/js/filament/editorjs.js', 'resources/js/filament/content-pages.js'])
