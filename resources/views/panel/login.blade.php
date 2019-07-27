<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('panel.layouts.head')
    @extends('panel.layouts.head_meta')

    @section('head-css-custom')
        <!-- Custom styles for this template-->
        <link href="{{ mix('admin-panel/login/css/styles.css') }}" rel="stylesheet" />
    @overwrite

    @section('title', 'API Admin - Login')
</head>

<body class="bg-dark">
    <div class="container">
        <div class="card card-login mx-auto mt-5">
            <div class="card-header">Login</div>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <div class="form-label-group">
                            <input type="email" id="inputEmail" class="form-control"
                                   placeholder="Email address" required="required"
                                   autofocus="autofocus">

                            <label for="inputEmail">Dirección Email</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-label-group">
                            <input type="password" id="inputPassword"
                                   class="form-control" placeholder="Password"
                                   required="required">
                            <label for="inputPassword">Contraseña</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" value="remember-me">
                                Recordar Contraseña
                            </label>
                        </div>
                    </div>
                    <a class="btn btn-primary btn-block" href="index.html">Login</a>
                </form>
                <div class="text-center">
                    <a class="d-block small mt-3" href="#" >
                        Registrar una cuenta
                    </a>
                    <a class="d-block small" href="forgot-password.html">
                        ¿Contraseña Perdida?
                    </a>
                </div>
            </div>
        </div>
    </div>

    @extends('panel.layouts.footer')
    @extends('panel.layouts.footer_meta')

    @section('footer-js-custom')
        <script src="{{ mix('/admin-panel/login/js/functions.js') }}"></script>
        <script src="{{ mix('/admin-panel/login/js/scripts.js') }}"></script>
    @overwrite
</body>
</html>
