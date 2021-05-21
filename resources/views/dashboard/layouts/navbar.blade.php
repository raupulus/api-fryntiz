<nav class="navbar navbar-expand navbar-dark bg-dark static-top">

    <a class="navbar-brand mr-1" href="{{route('panel.index')}}">
        {{ config('app.name') }}
    </a>

    <button class="btn btn-link btn-sm text-white order-1 order-sm-0"
            id="sidebarToggle"
            href="#">
        <i class="fas fa-bars"></i>
    </button>

    {{-- Navbar Search --}}
    <form class="d-none d-md-inline-block form-inline ml-auto mr-0 mr-md-3 my-2 my-md-0">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Buscar..."
                   aria-label="Search" aria-describedby="basic-addon2" />
            <div class="input-group-append">
                <button class="btn btn-primary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </form>

    <!-- Navbar -->
    <ul class="navbar-nav ml-auto ml-md-0">
        {{-- Notifications --}}
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="badge badge-danger">{{random_int(1,9)}}+</span>
                <i class="fas fa-bell fa-fw"></i>
            </a>

            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="alertsDropdown">
                <a class="dropdown-item" href="#">Nueva Notificación 1</a>
                <a class="dropdown-item" href="#">Nueva Notificación 2</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#">
                    Ver todas las notificaciones
                </a>
            </div>
        </li>

        {{-- Messages --}}
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="badge badge-danger">{{random_int(1,9)}}</span>
                <i class="fas fa-envelope fa-fw"></i>
            </a>

            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="messagesDropdown">
                <a class="dropdown-item" href="#">Nuevo Mensaje 1</a>
                <a class="dropdown-item" href="#">Nuevo Mensaje 2</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#">
                    Ver todos los mensajes
                </a>
            </div>
        </li>

        {{-- User --}}
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle"
               href="#"
               id="userDropdown"
               role="button"
               data-toggle="dropdown"
               aria-haspopup="true"
               aria-expanded="false">
                {{-- <i class="fas fa-user-circle fa-fw"></i> --}}
                <img src="{{auth()->user()->urlAvatar}}"
                     alt="{{auth()->user()->name}}"
                     title="{{auth()->user()->name}}"
                     class="navbar-user-image" />
            </a>

            <div class="dropdown-menu dropdown-menu-right"
                 aria-labelledby="userDropdown">
                <a class="dropdown-item"
                   href="{{route('panel.users.view', [
                           auth()->id(),
                           auth()->user()->nick
                   ])}}">
                    Perfil
                </a>

                <a class="dropdown-item" href="{{route('panel.users.configuration')}}">
                    Configuración
                </a>

                <a class="dropdown-item" href="#">Log de Actividad</a>

                <div class="dropdown-divider"></div>

                {{-- Botón para abrir modal de Logout --}}
                <a class="dropdown-item"
                   href="#"
                   data-toggle="modal"
                   data-target="#logoutModal">
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav>
