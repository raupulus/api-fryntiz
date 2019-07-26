<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <title>Api Fryntiz</title>
    </head>

    <body>
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
            ];
        @endphp

        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1>API - Modo depuración temporalmente</h1>

                    <p>
                        Datos no fiables durante el desarrollo, franja horaria
                        UTC
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
                        <h2>
                            {{$title}} -
                            <small>
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
                                            {{$collection->max('value')}}
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
                                            {{$collection->min('value')}}
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
                                            @php
                                                $max = $collection->min('value');
                                                $min = $collection->min('value');
                                                $media = ($max + $min)/ 2.000;
                                            @endphp
                                            {{$collection->avg('value')}}
                                        </strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <table class="table table-bordered table-dark">
                            <head>
                                <tr>
                                    <td>
                                        Timestamp
                                    </td>

                                    <td>
                                        Valor
                                    </td>
                                </tr>
                            </head>
                            <tbody>
                                @foreach($collection as $ele)
                                    <tr>
                                        <td>
                                            {{$ele->created_at}}
                                        </td>

                                        <td>
                                            {{$ele->value}}
                                        </td>
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
