@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', '')
@section('description', '')
@section('keywords', '')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', '')
@section('rs-sitename', '')
@section('rs-description', '')
@section('rs-image', '')
@section('rs-url', '')
@section('rs-image-alt', '')

@section('twitter-site', '')
@section('twitter-creator', '')

{{-- Marca el elemento del menú que se encuentra activo --}}
@section('active-index', 'active')

{{-- Marca el elemento del menú que se encuentra activo --}}
@section('active-index', 'active')

@section('header')
    <header class="">

    </header>
@endsection

@section('content')
    <div class="leading-normal tracking-normal"
         style="font-family: 'Source Sans Pro', sans-serif;">

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Contador de teclas pulsadas (KeyCounter)
                </h1>

                <div class="w-full py-3 px-6 text-center">
                    <p class="text-gray-600 mb-1">
                        Estadísticas desde {{$keyboard_statistics['period_start']}}
                        hasta {{$keyboard_statistics['period_end']}}
                    </p>
                </div>

                <div class="w-full mb-4 text-center">
                    <form action="{{route('keycounter.index')}}" method="GET">
                        <div class="relative inline-flex w-1/2">
                            <svg class="w-2 h-2 absolute top-0 right-0 m-4 pointer-events-none" xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 412 232">
                                <path d="M206 171.144L42.678 7.822c-9.763-9.763-25.592-9.763-35.355 0-9.763 9.764-9.763 25.592 0 35.355l181 181c4.88 4.882 11.279 7.323 17.677 7.323s12.796-2.441 17.678-7.322l181-181c9.763-9.764 9.763-25.592 0-35.355-9.763-9.763-25.592-9.763-35.355 0L206 171.144z"
                                      fill="#648299"
                                      fill-rule="nonzero"/>
                            </svg>

                            <select name="year"
                                    class="w-full border border-gray-300 rounded-full text-gray-600 h-10 pl-5 pr-10 bg-white hover:border-gray-400 focus:outline-none appearance-none">
                                @foreach(range(2019, date('Y')) as $y)
                                    <option value="{{$y}}" {{($year && ($year == $y)) ? 'selected' : ''}}>
                                        Año {{$y}}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="relative inline-flex w-1/2">
                            <svg class="w-2 h-2 absolute top-0 right-0 m-4 pointer-events-none" xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 412 232">
                                <path d="M206 171.144L42.678 7.822c-9.763-9.763-25.592-9.763-35.355 0-9.763 9.764-9.763 25.592 0 35.355l181 181c4.88 4.882 11.279 7.323 17.677 7.323s12.796-2.441 17.678-7.322l181-181c9.763-9.764 9.763-25.592 0-35.355-9.763-9.763-25.592-9.763-35.355 0L206 171.144z"
                                      fill="#648299"
                                      fill-rule="nonzero" />
                            </svg>

                            <select name="month"
                                    class="w-full border border-gray-300 rounded-full text-gray-600 h-10 pl-5 pr-10 bg-white hover:border-gray-400 focus:outline-none appearance-none">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{$m}}" {{($month && ($month == $m)) ? 'selected' : ''}}>
                                        {{$meses[$m - 1]}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>




                {{-- Información general sobre el proyecto --}}
                <div class="flex flex-wrap content-center">
                    <div class="w-full p-6">
                        <div class="bg-red-100 border border-red-400 m-2 mb-5 text-red-700 px-2 py-2 rounded relative"
                             role="alert">
                            <span class="inline text-sm">
                                Sitio
                                <strong>temporal</strong>
                                con la finalidad de detectar posibles
                                caídas/cuelgues o lecturas imprecisas en los
                                programas que desarrollo para obtener las
                                estadísticas de pulsaciones de teclado y ratón
                                que conforman este <strong>keycounter</strong>.

                                <br/>

                                Una vez acabada la aplicación, este sitio
                                desaparecerá quedando solo como una api privada
                                accesible desde un componente en Vue.js para mi
                                sitio web personal:
                                <a href="https://fryntiz.es"
                                   class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                                   type="button"
                                   target="_blank"
                                   title="Raúl Caro Pastorino web
                                   desarrollador backend chipiona">
                                    https://fryntiz.es
                                </a>
                            </span>
                        </div>


                        <p class="text-gray-600 mb-2">
                            <canvas id="line-chart" width="600" height="450"
                                    style="max-height: 600px; max-width: 1000px; margin: auto"></canvas>
                        </p>

                        <h3 class="text-3xl text-gray-800 font-bold leading-none mb-3">
                            Sobre esta api
                        </h3>

                        <p class="text-gray-600 mb-2">
                            Los datos pueden no ser precisos debido a que aún se
                            encuentra en depuración para detección de errores
                            en código o cálculos.
                        </p>

                        <p class="text-gray-600 mb-2">
                            La finalidad de esta aplicación es leer las
                            pulsaciones de teclado y ratón quedando de forma
                            anónima las teclas pulsadas por privacidad y
                            transmitiendo para ser almacenado en esta API
                            solamente las estadísticas generales por rachas.
                        </p>

                        <p class="text-gray-600 mb-2">
                            Las rachas para el contador de pulsaciones son
                            estadísticas de teclas pulsadas hasta que pasan
                            <strong>15</strong>
                            segundos sin que ninguna tecla sea pulsada, se
                            almacena el promedio de pulsaciones y
                            calcula velocidad media además de generar una
                            puntuación (<em>score</em>) que valora la racha
                            mediante un algoritmo propio y resulta en una
                            puntuación mayor cuanto más teclas pulsadas de
                            media en el tiempo (sin ser un valor lineal).
                        </p>

                        <p class="text-gray-600 mb-1">
                            La herramienta que he creado está sólo disponible
                            para sistemas <strong>GNU/LINUX</strong> pues es
                            el único sistema operativo con el que trabajo y
                            me interesa.

                            <br />

                            Ha sido construida utilizando python3, puedes ver
                            el desarrollo público bajo licencia GPLv3 en el
                            siguiente enlace:

                            <a href="https://gitlab.com/fryntiz/python-keycounter"
                               class="underline text-lightBlue-500 background-transparent font-bold text-xs outline-none focus:outline-none ease-linear transition-all duration-150"
                               type="button"
                               target="_blank"
                               title="Raspberry pi 4 estación meteorológica">
                                https://gitlab.com/fryntiz/python-keycounter
                            </a>
                        </p>

                        <div class="bg-yellow-100 border border-red-400 m-2 my-4 text-red-700 px-2 py-2 rounded relative text-center"
                             role="alert">
                            <span class="inline font-bold">
                                Franja horaria UTC +0:00
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>










    <div class="row">




        <div class="col-md-12 m-5 text-secondary text-danger">
            <div class="row">
                <div class="col-md-10 mx-auto text-left">
                    Total de rachas este mes:
                    <strong>
                        {{$keyboard_statistics['period_count']}}
                    </strong>
                </div>

                <div class="col-md-10 mx-auto text-left">
                    Total de puntuaciones este mes:
                    <strong>
                        {{$keyboard_statistics['period_total_pulsations']}}
                    </strong>
                </div>

                <div class="col-md-10 mx-auto text-left">
                    Pulsaciones máximas de un dispositivo en un día:
                    <strong>
                        {{$keyboard_statistics['data']->max('total_pulsations')}}
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-md-12 text-center">
            <h1>Estadísticas por cada 100 resultados</h1>
            <p>
                Registro en hora UTC
            </p>
        </div>

        <div class="card-deck m-5">
            <div class="card text-white bg-info mb-3" style="max-width: 18rem;">
                <div class="card-header">Total de pulsaciones</div>
                <div class="card-body">
                    <h5 class="card-title"></h5>
                    <p class="card-text">
                        {{$keyboard->sum('pulsations')}}
                    </p>
                </div>
            </div>

            <div class="card text-white bg-dark mb-3" style="max-width: 18rem;">
                <div class="card-header">Total de puntuación</div>
                <div class="card-body">
                    <h5 class="card-title"></h5>
                    <p class="card-text">
                        {{$keyboard->sum('score')}}
                    </p>
                </div>
            </div>

            <div class="card text-white bg-primary mb-3" style="max-width: 18rem;">
                <div class="card-header">Pulsaciones media</div>
                <div class="card-body">
                    <h5 class="card-title"></h5>
                    <p class="card-text">
                        {{$keyboard->avg('pulsations')}}
                    </p>
                </div>
            </div>

            <div class="card text-white bg-success mb-3" style="max-width: 18rem;">
                <div class="card-header">Pulsaciones media para teclas
                    especiales</div>
                <div class="card-body">
                    <h5 class="card-title"></h5>
                    <p class="card-text">
                        {{$keyboard->avg('pulsations_special_keys')}}
                    </p>
                </div>
            </div>
            <div class="card text-white bg-danger mb-3" style="max-width: 18rem;">
                <div class="card-header">Pulsaciones media por minuto</div>
                <div class="card-body">
                    <h5 class="card-title"></h5>
                    <p class="card-text">
                        {{$keyboard->avg('pulsation_average')}}
                    </p>
                </div>
            </div>
            <div class="card text-white bg-warning mb-3" style="max-width: 18rem;">
                <div class="card-header">Puntuación Media</div>
                <div class="card-body">
                    <h5 class="card-title"></h5>
                    <p class="card-text">
                        {{$keyboard->avg('score')}}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-12 text-center">
            <h1>KEYBOARD</h1>
        </div>

        <div class="col-md-12 p-5">
            <table class="table table-sm table-striped table-dark table-bordered table-hover"
                   style="width: 100%; overflow: scroll; display: block;">
                <thead>
                <tr>
                    <td>nº</td>
                    <td>start_at</td>
                    <td>end_at</td>
                    <td>duration</td>
                    <td>pulsations</td>
                    <td>pulsations_special_keys</td>
                    <td>pulsation_average</td>
                    <td>score</td>
                    <td>weekday</td>
                    <td>device_id</td>
                    <td>device_name</td>
                    <td>created_at</td>
                </tr>
                </thead>

                <tbody>
                @foreach($keyboard as $reg)
                    <tr>
                        <td>{{$reg->id}}</td>
                        <td>{{$reg->start_at}}</td>
                        <td>{{$reg->end_at}}</td>
                        <td>{{$reg->duration}}</td>
                        <td>{{$reg->pulsations}}</td>
                        <td>{{$reg->pulsations_special_keys}}</td>
                        <td>{{$reg->pulsation_average}}</td>
                        <td>{{$reg->score}}</td>
                        <td>{{$reg->weekday}}</td>
                        <td>{{$reg->device_id}}</td>
                        <td>{{$reg->device_name}}</td>
                        <td>{{$reg->created_at}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="col-md-12 text-center">
            <h1>MOUSE</h1>
        </div>

        <div class="col-md-12 p-5">
            <table class="table table-sm table-striped table-dark table-bordered table-hover"
                   style="max-width: 100%; overflow: scroll; display: block;">
                <thead>
                <tr>
                    <td>nº</td>
                    <td>start_at</td>
                    <td>end_at</td>
                    <td>duration</td>
                    <td>clicks_left</td>
                    <td>clicks_right</td>
                    <td>clicks_middle</td>
                    <td>total_clicks</td>
                    <td>clicks_average</td>
                    <td>weekday</td>
                    <td>device_id</td>
                    <td>device_name</td>
                    <td>created_at</td>
                </tr>
                </thead>

                <tbody>
                @foreach($mouse as $reg)
                    <tr>
                        <td>{{$reg->id}}</td>
                        <td>{{$reg->start_at}}</td>
                        <td>{{$reg->end_at}}</td>
                        <td>{{$reg->duration}}</td>
                        <td>{{$reg->clicks_left}}</td>
                        <td>{{$reg->clicks_right}}</td>
                        <td>{{$reg->clicks_middle}}</td>
                        <td>{{$reg->total_clicks}}</td>
                        <td>{{$reg->clicks_average}}</td>
                        <td>{{$reg->weekday}}</td>
                        <td>{{$reg->device_id}}</td>
                        <td>{{$reg->device_name}}</td>
                        <td>{{$reg->created_at}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="col-md-12 text-center">
            {!! $keyboard->links() !!}
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js"></script>

    <script>
        var labels = "{{$labelsString}}".split(',');
        var datasetJsonString = '<?= $datasetJson ?>';
        var dataset = JSON.parse(datasetJsonString);

        new Chart(document.getElementById("line-chart"), {
            type: 'line',
            data: {
                labels: labels,
                datasets: dataset
            },
            options: {
                title: {
                    display: true,
                    text: 'Gráfica de pulsaciones diarias por dispositivos',
                },
                scales:{
                    labelString: 'pppp',
                    yAxes:[{
                        scaleLabel: {
                            display: true,
                            labelString: 'Pulsaciones Totales'
                        }
                    }],
                    xAxes:[{
                        scaleLabel: {
                            display: true,
                            labelString: 'Días en el mes: {{$month ? $meses[$month-1] : 'actual'}}'
                        }
                    }],
                }
            }
        });
    </script>
@endsection
