{{-- Mis funciones generales --}}
<script src="{{ mix('js/functions.js') }}"></script>

{{-- Scripts que serán reemplazados por algunas páginas --}}
@section('footer-js-custom')
    <script src="{{ mix('js/scripts.js') }}"></script>
@show
