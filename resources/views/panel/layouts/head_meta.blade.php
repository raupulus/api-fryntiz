{{-- Bootstrap --}}
<link href="{{ mix('assets/css/bootstrap.css') }}" rel="stylesheet" />

{{-- Font Awesome --}}
<link href="{{ mix('assets/css/fontawesome.css') }}" rel="stylesheet" />

{{-- Estilos Propios --}}

{{-- Estilos que serán reemplazados por algunas páginas --}}
@section('head-css-custom')
    <!-- Page level plugin CSS-->
    <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">

    <link href="{{ mix('admin-panel/css/styles.css') }}" rel="stylesheet" />
@show
