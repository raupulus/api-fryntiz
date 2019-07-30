{{-- JQuery --}}
<script src="{{ asset('assets/js/jquery.js') }}"></script>

{{-- jquery.easing --}}
{{--
<script src="{{ mix('assets/js/jquery.easing.js') }}"></script>
--}}

{{-- Popper.js --}}
<script src="{{ mix('assets/js/popper.js') }}"></script>

{{-- Boostrap JS --}}
<script src="{{ mix('assets/js/bootstrap.js') }}"></script>

{{-- Fontawesome --}}
<script src="{{ mix('assets/js/fontawesome.js') }}"></script>

{{-- Scripts que serán reemplazados por algunas páginas --}}
@section('footer-js-custom')
    {{-- DataTables --}}
    <script src="{{ mix('assets/js/datatables.js') }}"></script>

    {{-- Chart.js --}}
    <script src="{{ mix('assets/js/chart.js') }}"></script>

    {{-- Demos scripts --}}
    {{--
    <script src="{{ url('admin-panel/js/demos/datatables-demo.js') }}"></script>
    <script src="{{ url('admin-panel/js/demos/chart-area-demo.js') }}"></script>
    <script src="{{ url('admin-panel/js/demos/chart-bar-demo.js') }}"></script>
    <script src="{{ url('admin-panel/js/demos/chart-pie-demo.js') }}"></script>
    --}}

    <script src="{{ mix('admin-panel/js/functions.js') }}"></script>
    <script src="{{ mix('admin-panel/js/scripts.js') }}"></script>


@show
