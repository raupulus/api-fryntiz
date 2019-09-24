<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <title>Api Fryntiz</title>
    </head>

    <body data-spy="scroll" data-target=".navbar" data-offset="50">
        @php
            $datos = [
                'Humedad' => \App\Humidity::whereNotNull('value')
                    ->orderBy('created_at', 'DESC')
                    ->paginate(20),
                'Temperatura' => \App\Temperature::whereNotNull('value')
                    ->orderBy('created_at', 'DESC')
                    ->paginate(20),
                'Presión' => \App\Pressure::whereNotNull('value')
                    ->orderBy('created_at', 'DESC')
                    ->paginate(20),
                'Luz' => \App\Light::whereNotNull('value')
                    ->orderBy('created_at', 'DESC')
                    ->paginate(20),
                'Rayos UV' => \App\Uv::whereNotNull('uv_raw')
                    ->orderBy('created_at', 'DESC')
                    ->paginate(20),
            ];
        @endphp

        {{-- Navbar --}}
        <nav class="navbar navbar-expand-sm navbar-dark bg-dark fixed-top">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo03" aria-controls="navbarTogglerDemo03" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="{{url('/')}}">API | WS</a>

            <div class="collapse navbar-collapse" id="navbarTogglerDemo03">
                <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
                    @foreach(array_keys($datos) as $titulo)
                        <li class="nav-item">
                            <a href="#{{$titulo}}" class="nav-link">
                                {{$titulo}}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </nav>

        <div class="container mt-5">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1>API - Modo depuración temporalmente</h1>

                    <p>
                        Datos no fiables durante el desarrollo, franja horaria
                        UTC
                    </p>

                    <p class="alert alert-danger text-left">
                        <span class="text-danger ">
                            Esta página es
                            <strong>temporal</strong>
                            solo para detectar posibles caídas/cuelgues en los
                            programas que componen tanto sensores, arduinos,
                            raspberrys, servidores... etc
                            <br />
                            Una vez acabada la aplicación desaparecerá quedando
                            en el subdominio:
                            <a href="https://eltiempo.desdechipiona.es"
                               title="El tiempo Desde Chipiona">
                                https://eltiempo.desdechipiona.es
                            </a>
                        </span>
                    </p>

                    <p>
                        Esta API se encuentra en construcción y mide el tiempo
                        actual en Chipiona con una frecuencia de 1-2 minutos.
                    </p>

                    <p>
                        Puedes ver el desarrollo aquí:

                        <a href="https://gitlab.com/fryntiz/api-fryntiz/tree/master"
                           title="Api fryntiz en GitLab">
                            https://gitlab.com/fryntiz/api-fryntiz
                        </a>
                    </p>

                    <p>
                        Puedes ver el desarrollo de la estación meteorológica
                        para la raspberry aquí:

                        <a href="https://gitlab.com/fryntiz/raspberry-weather-station"
                           title="Raspberry pi 4 estación meteorológica">
                            https://gitlab.com/fryntiz/raspberry-weather-station
                        </a>
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    @foreach($datos as $title => $collection)
                        @php
                            $att_fillables = $collection->get(0)->getFillable();
                        @endphp
                        <h2 id="{{$title}}">
                            {{$title}} -
                            <small>
                                Viendo últimos
                                <strong>{{$collection->count()}}</strong>
                                registros
                            </small>
                        </h2>

                        <div class="row">
                            <div class="card text-white bg-danger mb-3 mr-3
                            ml-3"
                                 style="width: 18rem">
                                <div class="card-header">
                                    Mayor valor registrado
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title"></h5>
                                    <p class="card-text text-center">
                                        <strong>
                                            @if($collection->count() >= 1)
                                                @if(in_array('value',$att_fillables))
                                                    {{$collection->max('value')}}
                                                @elseif (in_array('uv_raw',$att_fillables))
                                                    {{$collection->max('uv_raw')}}
                                                @elseif (in_array('average',$att_fillables))
                                                    {{$collection->max('average')}}
                                                @endif
                                            @endif
                                        </strong>
                                    </p>
                                </div>
                            </div>

                            <div class="card text-white bg-primary mb-3 mr-3
                            ml-3"
                                 style="width: 18rem;">
                                <div class="card-header">
                                    Menor valor registrado
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title"></h5>
                                    <p class="card-text text-center">
                                        <strong>
                                            @if($collection->count() >= 1)
                                                @if(in_array('value',$att_fillables))
                                                    {{$collection->min('value')}}
                                                @elseif (in_array('uv_raw',$att_fillables))
                                                    {{$collection->min('uv_raw')}}
                                                @elseif (in_array('average',$att_fillables))
                                                    {{$collection->min('average')}}
                                                @endif
                                            @endif
                                        </strong>
                                    </p>
                                </div>
                            </div>

                            <div class="card text-white bg-warning mb-3 mr-3
                            ml-3"
                                 style="width: 18rem;">
                                <div class="card-header">
                                    Valor Medio
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title"></h5>
                                    <p class="card-text text-center">
                                        <strong>
                                            @if($collection->count() >= 1)
                                                @if(in_array('value',$att_fillables))
                                                    {{$collection->avg('value')}}
                                                @elseif (in_array('uv_raw',$att_fillables))
                                                    {{$collection->avg('uv_raw')}}
                                                @elseif (in_array('average',$att_fillables))
                                                    {{$collection->avg('average')}}
                                                @endif
                                            @endif
                                        </strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <table class="table table-bordered table-dark">
                            <head>
                                <tr>
                                    @if($collection->count() >= 1)
                                        @foreach($att_fillables as $value)
                                            <td>
                                                {{$value}}
                                            </td>
                                        @endforeach
                                    @endif
                                </tr>
                            </head>

                            <tbody>
                                @foreach($collection as $ele)
                                    <tr>
                                        @php
                                        $x = $ele->getFillable();
                                        @endphp

                                        @foreach($att_fillables as $name)
                                            <td>
                                                {{$ele->$name}}
                                            </td>
                                        @endforeach

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Paginación --}}
                        {{$collection->links()}}
                    @endforeach
                </div>
            </div>
        </div>
    </body>
</html>
