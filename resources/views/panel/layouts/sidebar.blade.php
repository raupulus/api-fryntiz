<ul class="sidebar navbar-nav">
    <li class="nav-item active">
        <a class="nav-link" href="{{route('panel-index')}}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    {{-- Usuarios --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-user"></i>
            <span>Usuarios</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            <h6 class="dropdown-header">Usuarios WEB:</h6>
            <a class="dropdown-item" href="#">
                Añadir Usuario
            </a>
            <a class="dropdown-item" href="#">
                Gestionar Usuarios
            </a>

            <div class="dropdown-divider"></div>

            <h6 class="dropdown-header">Usuarios API:</h6>
            <a class="dropdown-item" href="#">
                Añadir Usuario
            </a>

            <a class="dropdown-item" href="#">
                Gestionar Usuarios
            </a>
        </div>
    </li>

    {{-- Páginas --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-folder"></i>
            <span>Páginas</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            <h6 class="dropdown-header">Ventanas de Login:</h6>
            <a class="dropdown-item" href="{{route('panel-login')}}">
                Login
            </a>
            <a class="dropdown-item" href="{{route('panel-register')}}">
                Registro
            </a>
            <a class="dropdown-item" href="{{route('panel-forgot-password')}}">
                Contraseña Perdida
            </a>

            <div class="dropdown-divider"></div>
            <h6 class="dropdown-header">Otras páginas:</h6>
            <a class="dropdown-item" href="{{route('panel-404')}}">
                404
            </a>

            <a class="dropdown-item" href="{{route('panel-blank')}}">
                En blanco
            </a>
        </div>
    </li>


    <li class="nav-item">
        <a class="nav-link" href="{{route('panel-demo-charts')}}">
            <i class="fas fa-fw fa-chart-area"></i>
            <span>Demo Charts</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{route('panel-demo-tables')}}">
            <i class="fas fa-fw fa-table"></i>
            <span>Tables</span></a>
    </li>
</ul>
