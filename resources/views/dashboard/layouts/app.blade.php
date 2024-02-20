<!DOCTYPE html>
<!--
@author Raúl Caro Pastorino
@copyright Copyright (c) 2019 Raúl Caro Pastorino
@license https://www.gnu.org/licenses/gpl-3.0-standalone.html

Author Web: https://raupulus.dev
E-mail: dev@fryntiz.es
-->

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('resources.global_vars_js')
    @include('dashboard.layouts.head')
    @include('dashboard.layouts.head_meta')
    @yield('head_css')
    @yield('head_javascript')
</head>

<body class="page-top header-sticky">
<div id="app">

    @include('dashboard.layouts.navbar')

    <div id="wrapper">
        @include('dashboard.layouts.aside')

        <div id="content-wrapper">

            <div class="container-fluid">
                {{-- Caja para las alertas, errores y notificaciones flash --}}
                <div id="box-flash">
                    @include('dashboard.partials.flash')
                </div>

                {{-- Caja con el contenido principal de la aplicación --}}
                <div id="box-content">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <footer id="box-footer" class="footer {{-- sticky-footer --}}">
        @include('dashboard.layouts.footer')
    </footer>

</div>

@yield('modal')
@include('dashboard.layouts.footer_meta')
@yield('css')
@yield('javascript')
@yield('js')
</body>
</html>
