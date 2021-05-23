{{-- Tailwind --}}
<link href="{{ mix('css/tailwind.css') }}" rel="stylesheet" />

{{-- Estilos Propios --}}

{{-- Estilos que serán reemplazados por algunas páginas --}}
@section('head-css-custom')
    <link href="{{ mix('css/styles.css') }}" rel="stylesheet" />
@show
