<!DOCTYPE html>
<!--
@author Raúl Caro Pastorino
@copyright Copyright (c) 2019 Raúl Caro Pastorino
@license https://www.gnu.org/licenses/gpl-3.0-standalone.html

Author Web: https://fryntiz.es
E-mail: dev@fryntiz.es
-->

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('resources.global_vars_js')
    @include('panel.layouts.head')
    @include('panel.layouts.head_meta')
    @yield('head_css')
    @yield('head_javascript')
</head>

<body class="page-top header-sticky">
<div id="app">

    @include('panel.layouts.navbar')

    <div id="wrapper">
        @include('panel.layouts.sidebar')

        <div id="content-wrapper">

            <div class="container-fluid">
                {{-- Caja para las alertas, errores y notificaciones --}}
                <div id="box-alerts">
                    @include('panel.alerts.all_messages')
                </div>

                {{-- Caja con el contenido principal de la aplicación --}}
                <div id="box-content">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <footer id="box-footer" class="footer {{-- sticky-footer --}}">
        @include('panel.layouts.footer')
    </footer>

</div>

@yield('modal')
@include('panel.layouts.footer_meta')
@yield('css')
@yield('javascript')
@yield('js')
</body>
</html>
