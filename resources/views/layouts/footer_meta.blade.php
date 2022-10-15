<script src="{{ mix('js/app.js') }}"></script>
<script src="{{ mix('vendor/flowbite/flowbite.js') }}"></script>


{{-- Scripts que serán reemplazados por algunas páginas --}}
@section('footer-js-custom')
    <script src="{{ mix('js/scripts.js') }}"></script>
@show

