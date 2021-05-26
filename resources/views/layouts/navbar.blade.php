@php($path = request()->path())
@php($classSelected = 'navbar-menu-element-current')

<nav class="fixed w-full top-0 flex items-center justify-between flex-wrap
p-2 navbar-main">
    <div class="flex items-center flex-no-shrink text-white mr-6">
        <img src="{{asset('images/logo-64x64.png')}}"
             alt="Logo Api Fryntiz"
             class="w-16"/>
        &nbsp;
        &nbsp;
        <a href="{{route('home')}}"
           class="navbar-site-title {{($path == '/') ? $classSelected : ''}}" >
            Api Fryntiz
        </a>
    </div>

    <div class="block lg:hidden">
        <button class="btn-toggle-nav-menu flex items-center px-3 py-2 border rounded text-teal-lighter border-teal-light hover:text-white hover:border-white">

            <svg class="h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <title>Menu</title>
                <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/>
            </svg>
        </button>
    </div>

    <div class="box-nav-menu-mobile w-full flex-grow hidden text-center lg:flex
    lg:items-center
    lg:w-auto">



        @php(
            $menuElements = [
                'weather_station' => [
                    'route' => route('weather_station.index'),
                    'name' => 'Weather Station'
                ],
                'keycounter' => [
                    'route' => route('keycounter.index'),
                    'name' => 'Keycounter'
                ],
                'airflight' => [
                    'route' => route('airflight.index'),
                    'name' => 'Airflight'
                ],
                'smartplant' => [
                    'route' => route('smartplant.index'),
                    'name' => 'Smart Plant'
                ],
            ]
        )

        <div class="text-sm lg:flex-grow">
            @foreach($menuElements as $idx => $ele)
                <a href="{{$ele['route']}}"

                   class="navbar-menu-element block mt-4 lg:inline-block lg:mt-0 text-teal-lighter hover:text-white {{($path == $idx) ? $classSelected : ''}}" >
                    {{$ele['name']}}
                </a>
            @endforeach

            <a href="https://fryntiz.es/contact"
               target="_blank"
               class="navbar-menu-element block mt-4 lg:inline-block lg:mt-0 text-teal-lighter hover:text-white">
                Contacto
            </a>
        </div>
    </div>
</nav>
