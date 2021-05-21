<ul class="sidebar navbar-nav">
    <li class="nav-item active">
        <a class="nav-link" href="{{route('panel.index')}}">
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
            <a class="dropdown-item" href="{{route('panel.users.add')}}">
                Añadir Usuario
            </a>
            <a class="dropdown-item" href="{{route('panel.users.index')}}">
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

    {{-- Contenido --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-folder"></i>
            <span>Contenido</span>
        </a>

        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            {{--
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
            --}}

            <a class="dropdown-item text-center"
               href="{{route('panel.content.index')}}">
                Ver todo
            </a>

            <a class="dropdown-item text-center"
               href="{{route('panel.category.index')}}">
                Categorías
            </a>

            @foreach(\App\ContentType::all() as $type)
                <h6 class="dropdown-header">{{$type->plural_name}}:</h6>

                <a class="dropdown-item"
                   href="{{route('panel.content.index', ['type_slug' => $type->slug])}}">
                    Listar {{$type->plural_name}}
                </a>

                <a class="dropdown-item"
                   href="{{route('panel.content.add', ['type_slug' => 'page'])}}">
                    Crear {{$type->name}}
                </a>

                <div class="dropdown-divider"></div>
            @endforeach
        </div>
    </li>

    {{-- Traducciones --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-folder"></i>
            <span>Traducciones</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            <h6 class="dropdown-header">Idiomas:</h6>
            <a class="dropdown-item" href="{{route('panel.register')}}">
                Gestionar Idiomas
            </a>

            <div class="dropdown-divider"></div>

            <h6 class="dropdown-header">Grupos</h6>
            <a class="dropdown-item" href="{{route('panel.404')}}">
                Gestionar Grupos
            </a>
        </div>
    </li>

    {{-- Configuraciones --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-folder"></i>
            <span>Configuraciones</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            <h6 class="dropdown-header">Web:</h6>

            <a class="dropdown-item" href="{{route('panel.login')}}">
                Header
            </a>
            <a class="dropdown-item" href="{{route('panel.login')}}">
                Footer
            </a>
            <a class="dropdown-item" href="{{route('panel.login')}}">
                General
            </a>

            <div class="dropdown-divider"></div>

            <h6 class="dropdown-header">Facturación</h6>
            <a class="dropdown-item" href="{{route('panel.404')}}">
                Datos de empresa
            </a>
            <a class="dropdown-item" href="{{route('panel.404')}}">
                SEPA
            </a>
            <a class="dropdown-item" href="{{route('panel.404')}}">
                Stripe
            </a>
            <a class="dropdown-item" href="{{route('panel.404')}}">
                Redsys
            </a>
            <a class="dropdown-item" href="{{route('panel.404')}}">
                Paypal
            </a>

            <div class="dropdown-divider"></div>

            <h6 class="dropdown-header">Legal</h6>
            <a class="dropdown-item" href="{{route('panel.404')}}">
                RGPD
            </a>

            <a class="dropdown-item" href="{{route('panel.404')}}">
                Cookies
            </a>

            <a class="dropdown-item" href="{{route('panel.404')}}">
                Políticas de Privacidad
            </a>
        </div>
    </li>

    {{-- Estadísticas --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-folder"></i>
            <span>Estadísticas</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            <h6 class="dropdown-header">Estadísticas:</h6>
            <a class="dropdown-item" href="{{route('panel.login')}}">
                Visitas
            </a>
            <a class="dropdown-item" href="{{route('panel.register')}}">
                Ventas
            </a>
        </div>
    </li>

    {{-- Analítica --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-folder"></i>
            <span>Analítica</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            <h6 class="dropdown-header">Google:</h6>
            <a class="dropdown-item" href="{{route('panel.login')}}">
                Configurar Adwords
            </a>

            <a class="dropdown-item" href="{{route('panel.login')}}">
                Configurar Analytics
            </a>

            <div class="dropdown-divider"></div>

            <h6 class="dropdown-header">Mi analítica:</h6>
            <a class="dropdown-item" href="{{route('panel.404')}}">
                Resumen
            </a>

            <a class="dropdown-item" href="{{route('panel.blank')}}">
                Gestionar
            </a>
        </div>
    </li>

    {{-- Facturación --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-folder"></i>
            <span>Facturación</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            <h6 class="dropdown-header">Resumen:</h6>
            <a class="dropdown-item" href="{{route('panel.login')}}">
                Ventas por Mes
            </a>
            <a class="dropdown-item" href="{{route('panel.register')}}">
                Ventas por Día
            </a>
            <a class="dropdown-item" href="{{route('panel.register')}}">
                Ventas por Año
            </a>
        </div>
    </li>

    {{-- File Manager --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-file"></i>
            <span>File Manager</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            <h6 class="dropdown-header">Directorios:</h6>
            <a class="dropdown-item" href="{{route('file_manager.index', ['type' => 'files'])}}">
                Archivos
            </a>
            <a class="dropdown-item" href="{{route('file_manager.index', ['type' => 'images'])}}">
                Imágenes
            </a>
            <a class="dropdown-item" href="{{route('file_manager.index', ['type' => 'posts'])}}">
                Posts
            </a>
        </div>
    </li>

    {{-- Páginas de Ejemplo --}}
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           id="pagesDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            <i class="fas fa-fw fa-folder"></i>
            <span>Páginas Ejemplo</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="pagesDropdown">
            <h6 class="dropdown-header">Ventanas de Login:</h6>
            <a class="dropdown-item" href="{{route('panel.login')}}">
                Login
            </a>
            <a class="dropdown-item" href="{{route('panel.register')}}">
                Registro
            </a>
            <a class="dropdown-item" href="{{route('panel.forgot.password')}}">
                Contraseña Perdida
            </a>

            <div class="dropdown-divider"></div>

            <h6 class="dropdown-header">Otras páginas:</h6>
            <a class="dropdown-item" href="{{route('panel.404')}}">
                404
            </a>

            <a class="dropdown-item" href="{{route('panel.blank')}}">
                En blanco
            </a>
        </div>
    </li>


    <li class="nav-item">
        <a class="nav-link" href="{{route('panel.demo.charts')}}">
            <i class="fas fa-fw fa-chart-area"></i>
            <span>Demo Charts</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{route('panel.demo.tables')}}">
            <i class="fas fa-fw fa-table"></i>
            <span>Demo Tables</span></a>
    </li>
</ul>
