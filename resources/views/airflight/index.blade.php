<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link
        href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
        crossorigin="anonymous">
    <title>Vuelos en Chipiona</title>
</head>

<body data-spy="scroll" data-target=".navbar" data-offset="50">

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12 text-center">
            <h1>API - Modo depuración temporalmente</h1>

            <p>
                Datos no fiables ni sitio final, solo se muestran datos para
                depurar subida e integridad de datos.
            </p>

            <p class="alert alert-danger text-left">
                <span class="text-danger ">
                    Los datos pueden estar manipulados puntualmente o ser borrados
                    inesperadamente ya que a veces el objetivo es preparar
                    entornos en los que quiero observar el comportamiento ante
                    ciertas situaciones.

                    <br/>

                    Aún así los datos mostrados son tomados por una
                    capturadora de televisión digital que alcanza el rango 1090Mhz

                    <br/>

                    Una vez acabada la aplicación desaparecerá quedando
                    en el subdominio:
                    <a href="https://eltiempo.desdechipiona.es"
                       title="El tiempo Desde Chipiona">
                        https://eltiempo.desdechipiona.es
                    </a>
                </span>
            </p>

            <p>
                Puedes ver el desarrollo de la API aquí:

                <a href="https://gitlab.com/fryntiz/api-fryntiz/tree/master"
                   title="Api fryntiz en GitLab">
                    https://gitlab.com/fryntiz/api-fryntiz
                </a>
            </p>

            <p>
                Puedes ver el desarrollo del programa para obtener los datos
                de los aviones detectados con la tarjeta de televisión y una
                raspberry pi:

                <a href="https://gitlab.com/fryntiz/dump1090-to-db"
                   title="Exportador dump1090 a db postgresql">
                    https://gitlab.com/fryntiz/dump1090-to-db
                </a>
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered table-dark">
                <head>
                    <tr>
                        <th>
                            ICAO
                        </th>

                        <th>
                            Categoría
                        </th>

                        <th>
                            Visto primera vez
                        </th>

                        <th>
                            Visto última vez
                        </th>
                    </tr>
                </head>

                <tbody>
                @foreach($planes as $plane)
                    <tr>
                        <td>
                            {{$plane->icao}}
                        </td>

                        <td>
                            {{$plane->category}}
                        </td>

                        <td>
                            {{$plane->seen_first_at}}
                        </td>

                        <td>
                            {{$plane->seen_last_at}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{-- Paginación --}}
            {{$planes->links()}}
        </div>
    </div>
</div>
</body>
</html>

