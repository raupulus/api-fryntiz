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
                'Humedad' => \App\Humidity::paginate(20),
                'Temperatura' => \App\Temperature::paginate(20),
                'Presión' => \App\Pressure::paginate(20),
            ];
        @endphp

        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1>API - Modo depuración temporalmente</h1>

                    <p>
                        Datos no reales/fiables
                    </p>

                    <p>
                        Esta API se encuentra en construcción.
                    </p>

                    <p>
                        Puedes ver el desarrollo aquí:
                        <a href="https://gitlab.com/fryntiz/api-fryntiz/tree/master"
                           title="Api fryntiz en GitLab">
                            https://gitlab.com/fryntiz/api-fryntiz
                        </a>
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    @foreach($datos as $title => $collection)
                        <h2>{{$title}}</h2>
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
