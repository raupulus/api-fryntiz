{{--

@extends('dashboard.layouts.app')


@section('content')
    <h1>
        Página principal
    </h1>
@endsection
--}}


@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <p>Welcome to this beautiful admin panel.</p>

    <div class="row">
        <div class="col-md-4">
            <x-adminlte-profile-widget class="elevation-4"
                                       name="Willian Dubling"
                                       desc="Web Developer"
                                       img="https://picsum.photos/id/177/100"
                                       cover="https://picsum.photos/id/541/550/200"
                                       header-class="text-white text-right"
                                       footer-class='bg-gradient-dark'/>
            <x-adminlte-profile-row-item title="4+ years of experience with"
                                         class="text-center border-bottom border-secondary"/>
            <x-adminlte-profile-col-item title="Javascript"
                                         icon="fab fa-2x fa-js text-orange"
                                         size="3"/>
            <x-adminlte-profile-col-item title="PHP"
                                         icon="fab fa-2x fa-php text-orange"
                                         size="3"/>
            <x-adminlte-profile-col-item title="HTML5"
                                         icon="fab fa-2x fa-html5 text-orange"
                                         size="3"/>
            <x-adminlte-profile-col-item title="CSS3"
                                         icon="fab fa-2x fa-css3 text-orange"
                                         size=3/>
                <x-adminlte-profile-col-item title="Angular"
                                             icon="fab fa-2x fa-angular text-orange"
                                             size=4/>
                    <x-adminlte-profile-col-item title="Bootstrap"
                                                 icon="fab fa-2x fa-bootstrap text-orange"
                                                 size=4/>
                        <x-adminlte-profile-col-item title="Laravel"
                                                     icon="fab fa-2x fa-laravel text-orange"
                                                     size=4/>
                            </x-adminlte-profile-widget>

        </div>
    </div>

@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script> console.log('Hi!'); </script>
@stop
