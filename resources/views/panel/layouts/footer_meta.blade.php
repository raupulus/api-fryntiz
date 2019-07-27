{{-- JQuery --}}
<script src="{{ mix('assets/js/jquery.js') }}"></script>

{{-- Boostrap JS --}}
<script src="{{ mix('assets/js/bootstrap.js') }}"></script>

{{-- Popper.js --}}
<script src="{{ mix('assets/js/popper.js') }}"></script>

{{-- Scripts personalizados --}}

{{-- Scripts que serán reemplazados por algunas páginas --}}
@section('footer-js-custom')
    <!-- Page level plugin JavaScript-->
    <script src="vendor/chart.js/Chart.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin.min.js"></script>

    <!-- Demo scripts for this page-->
    <script src="js/demo/datatables-demo.js"></script>
    <script src="js/demo/chart-area-demo.js"></script>

    <script src="{{ mix('admin-panel/js/functions.js') }}"></script>
    <script src="{{ mix('admin-panel/js/scripts.js') }}"></script>
@show
