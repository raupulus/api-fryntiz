<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="{{route('home')}}">
            {{config('app.name')}}
        </a>

        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#navbarResponsive"
                aria-controls="navbarResponsive"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item @yield('active-index')">
                    <a class="nav-link" href="{{route('home')}}">
                        Inicio
                        <span class="sr-only">(current)</span>
                    </a>
                </li>

                <li class="nav-item @yield('active-service')">
                    <a class="nav-link" href="#">
                        Servicios
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#"
                       id="navbarDropdown"
                       role="button"
                       data-toggle="dropdown"
                       aria-haspopup="true"
                       aria-expanded="false">
                        Secciones
                    </a>
                    <!-- Here's the magic. Add the .animate and .slide-in classes to your .dropdown-menu and you're all set! -->
                    <div class="dropdown-menu dropdown-menu-right animate slideIn" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="#">
                            Sección 1
                        </a>

                        <a class="dropdown-item" href="#">
                            Sección 2
                        </a>

                        <a class="dropdown-item" href="#">
                            Sección 3
                        </a>

                        <div class="dropdown-divider"></div>

                        <a class="dropdown-item" href="#">
                            Otra sección
                        </a>
                    </div>
                </li>

                <li class="nav-item @yield('active-about')">
                    <a class="nav-link" href="#">
                        About
                    </a>
                </li>

                <li class="nav-item @yield('active-contact')">
                    <a class="nav-link" href="#">
                        Contacto
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
