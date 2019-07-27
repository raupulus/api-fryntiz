<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('panel.layouts.head')
        @include('panel.layouts.head_meta')
        @yield('head_css')
        @yield('head_javascript')
    </head>

    <body class="header-sticky">
         @include('panel.layouts.navbar')

        <div id="box-content">
            @yield('content')
        </div>

        <footer id="box-footer" class="footer">
            @include('panel.layouts.footer')
        </footer>

         @include('panel.layouts.footer_meta')
         @yield('css')
         @yield('javascript')
    </body>
</html>
