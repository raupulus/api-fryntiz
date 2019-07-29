<div class="container">
    <div class="card card-register mx-auto mt-5">
        <div class="card-header">Registrar una Cuenta</div>
        <div class="card-body">
            <form>
                <div class="form-group">
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-label-group">
                                <input type="text" id="firstName" class="form-control" placeholder="First name" required="required" autofocus="autofocus">
                                <label for="firstName">Nombre</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-label-group">
                                <input type="text" id="lastName" class="form-control" placeholder="Last name" required="required">
                                <label for="lastName">Apellidos</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-label-group">
                        <input type="email" id="inputEmail" class="form-control" placeholder="Email address" required="required">
                        <label for="inputEmail">Dirección Email</label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-label-group">
                                <input type="password" id="inputPassword" class="form-control" placeholder="Password" required="required">
                                <label for="inputPassword">Password</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-label-group">
                                <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm password" required="required">
                                <label for="confirmPassword">
                                    Confirmar password
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <a class="btn btn-primary btn-block" href="{{route('panel-login')}}">
                    Registrar
                </a>
            </form>
            <div class="text-center">
                <a class="d-block small mt-3" href="{{route('panel-login')}}">
                    Página de Login
                </a>
                <a class="d-block small" href="{{route('panel-forgot-password')}}">
                    ¿Contraseña Perdida?
                </a>
            </div>
        </div>
    </div>
</div>
