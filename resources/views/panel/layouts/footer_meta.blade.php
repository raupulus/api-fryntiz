{{-- JQuery --}}

{{-- Boostrap JS, Incluye Popper.js --}}
<script src="{{ mix('assets/js/bootstrap.js') }}"></script>

{{-- Jquery Esasing --}}
<script src="{{ mix('assets/js/jquery.easing.js') }}"></script>

{{-- Scripts personalizados --}}

{{-- Scripts que serán reemplazados por algunas páginas --}}
@section('footer-js-custom')
    <script src="{{ mix('admin-panel/js/functions.js') }}"></script>
    <script src="{{ mix('admin-panel/js/scripts.js') }}"></script>
@show
