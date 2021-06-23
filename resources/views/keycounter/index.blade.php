@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', 'Keycounter && Mousecounter')
@section('description', 'Contador de pulsaciones de teclado y ratón por rachas')
@section('keywords', 'keycounter, mousecounter, teclado, Raúl Caro Pastorino, fryntiz, ratón, pulsaciones de teclado, pulsaciones de ratón, contador de pulsaciones')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', 'Keycounter && Mousecounter, contador de pulsaciones')
@section('rs-sitename', 'Api Fryntiz')
@section('rs-description', 'Contador de pulsaciones de teclado y ratón por rachas')
@section('rs-image', asset('images/keycounter/social-thumbnail.jpg'))
@section('rs-url', route('keycounter.index'))
@section('rs-image-alt', 'Contador de pulsaciones de teclado y ratón por rachas')

@section('meta-twitter-title', 'Keycounter && Mousecounter, contador de pulsaciones')

@section('content')
    <div class="leading-normal tracking-normal"
         style="font-family: 'Source Sans Pro', sans-serif;">

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Contador de teclas pulsadas (KeyCounter && Mousecounter)
                </h1>

                <div class="w-full py-3 px-6 text-center">
                    <p class="text-gray-600 mb-1">
                        Estadísticas
                        desde {{$keyboard_statistics['period_start']}}
                        hasta {{$keyboard_statistics['period_end']}}
                    </p>
                </div>

                <div class="w-full mb-4 text-center">
                    <form id="form-filter"
                          action="{{route('keycounter.index')}}"
                          method="GET">
                        <div class="relative inline-flex w-1/2">
                            <svg
                                class="w-2 h-2 absolute top-0 right-0 m-4 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 412 232">
                                <path
                                    d="M206 171.144L42.678 7.822c-9.763-9.763-25.592-9.763-35.355 0-9.763 9.764-9.763 25.592 0 35.355l181 181c4.88 4.882 11.279 7.323 17.677 7.323s12.796-2.441 17.678-7.322l181-181c9.763-9.764 9.763-25.592 0-35.355-9.763-9.763-25.592-9.763-35.355 0L206 171.144z"
                                    fill="#648299"
                                    fill-rule="nonzero"/>
                            </svg>

                            <select name="year"
                                    class="keycounter-date-select w-full border border-gray-300 rounded text-gray-600 h-10 pl-5 pr-10 bg-white hover:border-gray-400 focus:outline-none appearance-none">
                                @foreach(range(date('Y'), 2019) as $y)
                                    <option
                                        value="{{$y}}" {{($year && ($year == $y)) ? 'selected' : ''}}>
                                        Año {{$y}}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="relative inline-flex w-1/2">
                            <svg
                                class="w-2 h-2 absolute top-0 right-0 m-4 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 412 232">
                                <path
                                    d="M206 171.144L42.678 7.822c-9.763-9.763-25.592-9.763-35.355 0-9.763 9.764-9.763 25.592 0 35.355l181 181c4.88 4.882 11.279 7.323 17.677 7.323s12.796-2.441 17.678-7.322l181-181c9.763-9.764 9.763-25.592 0-35.355-9.763-9.763-25.592-9.763-35.355 0L206 171.144z"
                                    fill="#648299"
                                    fill-rule="nonzero"/>
                            </svg>

                            <select name="month"
                                    class="keycounter-date-select w-full border border-gray-300 rounded text-gray-600 h-10 pl-5 pr-10 bg-white hover:border-gray-400 focus:outline-none appearance-none">
                                @foreach(range(1, 12) as $m)
                                    <option
                                        value="{{$m}}" {{($month && ($month == $m)) ? 'selected' : ''}}>
                                        {{$months[$m - 1]}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>


                {{-- Información general sobre el proyecto --}}
                <div class="flex flex-wrap content-center">
                    <div class="w-full p-6">
                        <div
                            class="bg-red-100 border border-red-400 m-2 mb-5 text-red-700 px-2 py-2 rounded relative"
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

                            <br/>

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

                        <div
                            class="bg-yellow-100 border border-red-400 m-2 my-4 text-red-700 px-2 py-2 rounded relative text-center"
                            role="alert">
                            <span class="inline font-bold">
                                Franja horaria UTC +0:00
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Resumen del mes
                </h1>

                <div class="w-full bg-gray-200 flex items-center justify-center px-5 py-5">
                    <div class="w-full max-w-3xl">
                        <div class="-mx-2 md:flex">
                            <div class="w-full md:w-1/3 px-2">
                                <div class="rounded-lg shadow-sm mb-4">
                                    <div class="rounded-lg bg-white shadow-lg md:shadow-xl relative overflow-hidden">
                                        <div class="px-3 pt-8 pb-10 text-center relative">
                                            <h4 class="text-sm uppercase text-gray-500 leading-tight">
                                                Total de rachas
                                            </h4>

                                            <h3 class="text-3xl text-gray-700 font-semibold leading-tight my-3">
                                                {{$keyboard_statistics['period_count']}}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/3 px-2">
                                <div class="rounded-lg shadow-sm mb-4">
                                    <div class="rounded-lg bg-white shadow-lg md:shadow-xl relative overflow-hidden">
                                        <div class="px-3 pt-8 pb-10 text-center relative">
                                            <h4 class="text-sm uppercase text-gray-500 leading-tight">
                                                Total de puntuaciones
                                            </h4>

                                            <h3 class="text-3xl text-gray-700 font-semibold leading-tight my-3">
                                                {{$keyboard_statistics['period_total_pulsations']}}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/3 px-2">
                                <div class="rounded-lg shadow-sm mb-4">
                                    <div class="rounded-lg bg-white shadow-lg md:shadow-xl relative overflow-hidden">
                                        <div class="px-3 pt-8 pb-10 text-center relative">
                                            <h4 class="text-sm uppercase text-gray-500 leading-tight">
                                                Max. Puls./disp. en 1 día
                                            </h4>

                                            <h3 class="text-3xl text-gray-700 font-semibold leading-tight my-3">
                                                {{$keyboard_statistics['data']->max('total_pulsations')}}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Resumen  últimos 100 resultados
                </h1>

                <div class="w-full bg-gray-200 flex items-center justify-center px-5 py-5">
                    <div class="w-full max-w-3xl">
                        <div class="-mx-2 md:flex">
                            <div class="w-full md:w-1/3 px-2">
                                <div class="rounded-lg shadow-sm mb-4">
                                    <div class="rounded-lg bg-white shadow-lg md:shadow-xl relative overflow-hidden">
                                        <div class="px-3 pt-8 pb-10 text-center relative">
                                            <h4 class="text-sm uppercase text-gray-500 leading-tight">
                                                Total de pulsaciones
                                            </h4>

                                            <h3 class="text-3xl text-gray-700 font-semibold leading-tight my-3">
                                                {{$keyboard->sum('pulsations')}}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/3 px-2">
                                <div class="rounded-lg shadow-sm mb-4">
                                    <div class="rounded-lg bg-white shadow-lg md:shadow-xl relative overflow-hidden">
                                        <div class="px-3 pt-8 pb-10 text-center relative">
                                            <h4 class="text-sm uppercase text-gray-500 leading-tight">
                                                Puntuación Total
                                            </h4>

                                            <h3 class="text-3xl text-gray-700 font-semibold leading-tight my-3">
                                                {{$keyboard->sum('score')}}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/3 px-2">
                                <div class="rounded-lg shadow-sm mb-4">
                                    <div class="rounded-lg bg-white shadow-lg md:shadow-xl relative overflow-hidden">
                                        <div class="px-3 pt-8 pb-10 text-center relative">
                                            <h4 class="text-sm uppercase text-gray-500 leading-tight">
                                                Pulsaciones media
                                            </h4>

                                            <h3 class="text-3xl text-gray-700 font-semibold leading-tight my-3">
                                                {{$keyboard->avg('pulsations')}}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="-mx-2 md:flex">
                            <div class="w-full md:w-1/3 px-2">
                                <div class="rounded-lg shadow-sm mb-4">
                                    <div class="rounded-lg bg-white shadow-lg md:shadow-xl relative overflow-hidden">
                                        <div class="px-3 pt-8 pb-10 text-center relative">
                                            <h4 class="text-sm uppercase text-gray-500 leading-tight">
                                                Media teclas especiales
                                            </h4>

                                            <h3 class="text-3xl text-gray-700 font-semibold leading-tight my-3">
                                                {{$keyboard->avg('pulsations_special_keys')}}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/3 px-2">
                                <div class="rounded-lg shadow-sm mb-4">
                                    <div class="rounded-lg bg-white shadow-lg md:shadow-xl relative overflow-hidden">
                                        <div class="px-3 pt-8 pb-10 text-center relative">
                                            <h4 class="text-sm uppercase text-gray-500 leading-tight">
                                                Pulsaciones por minuto
                                            </h4>

                                            <h3 class="text-3xl text-gray-700 font-semibold leading-tight my-3">
                                                {{$keyboard->avg('pulsation_average')}}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full md:w-1/3 px-2">
                                <div class="rounded-lg shadow-sm mb-4">
                                    <div class="rounded-lg bg-white shadow-lg md:shadow-xl relative overflow-hidden">
                                        <div class="px-3 pt-8 pb-10 text-center relative">
                                            <h4 class="text-sm uppercase text-gray-500 leading-tight">
                                                Puntuación Media
                                            </h4>

                                            <h3 class="text-3xl text-gray-700 font-semibold leading-tight my-3">
                                                {{$keyboard->avg('score')}}
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4 overflow-x-scroll">
                <h2>KEYBOARD</h2>

                <table class="min-w-max w-full table-auto">
                    <thead class="justify-between">
                    <tr class="bg-gray-800">
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            nº
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            start_at
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            end_at
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            duration
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            pulsations
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            pulsations_special_keys
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            pulsation_average
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            score
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            weekday
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            device_id
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            device_name
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            created_at
                        </td>
                    </tr>
                    </thead>

                    <tbody class="bg-gray-200">
                    @foreach($keyboard as $reg)
                        <tr class="bg-white border-4 border-gray-200">
                            <td class="px-1 py-2 items-center text-center">{{$reg->id}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->start_at}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->end_at}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->duration}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->pulsations}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->pulsations_special_keys}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->pulsation_average}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->score}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->weekday}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->device_id}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->device_name}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->created_at}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4 overflow-x-scroll">
                <h2>MOUSE</h2>

                <table class="min-w-max w-full table-auto">
                    <thead class="justify-between">
                    <tr class="bg-gray-800">
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            nº
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            start_at
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            end_at
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            duration
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            clicks_left
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            clicks_right
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            clicks_middle
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            total_clicks
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            clicks_average
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            weekday
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            device_id
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            device_name
                        </td>
                        <td class="px-1 py-2 text-gray-300 capitalize text-center">
                            created_at
                        </td>
                    </tr>
                    </thead>

                    <tbody class="bg-gray-200">
                    @foreach($mouse as $reg)
                        <tr class="bg-white border-4 border-gray-200">
                            <td class="px-1 py-2 items-center text-center">{{$reg->id}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->start_at}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->end_at}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->duration}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->clicks_left}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->clicks_right}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->clicks_middle}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->total_clicks}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->clicks_average}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->weekday}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->device_id}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->device_name}}</td>
                            <td class="px-1 py-2 items-center text-center">{{$reg->created_at}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@section('js')
    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js"></script>

    <script>
        var labels = "{{$labelsString}}".split(',');
        var datasetJsonString = '<?= $datasetJson ?>';
        var dataset = JSON.parse(datasetJsonString);

        var chartjs = new Chart(document.getElementById("line-chart"), {
            type:'line',
            data:{
                labels:labels,
                datasets:dataset
            },
            options:{
                title:{
                    display:true,
                    text:'Gráfica de pulsaciones diarias por dispositivos',
                },
                scales:{
                    labelString:'pppp',
                    yAxes:[{
                        scaleLabel:{
                            display:true,
                            labelString:'Pulsaciones Totales'
                        }
                    }],
                    xAxes:[{
                        scaleLabel:{
                            display:true,
                            labelString:'Días en el mes: {{$month ? $months[$month-1] : 'actual'}}'
                        }
                    }],
                }
            }
        });

        /**
         * Realiza la petición ajax para traer los datos de las pulsaciones de
         * teclado desde el servidor.
         */
        function getKeyboardData() {
            // TODO → Crear método para traer por ajax los datos.
            // TODO → Securizar al traer datos usando token validado por cookie

            return true;
        }

        /**
         * Realiza la petición ajax para traer los datos de las pulsaciones de
         * ratón desde el servidor.
         */
        function getMouseData() {
            // TODO → Crear método para traer por ajax los datos.
            // TODO → Securizar al traer datos usando token validado por cookie

            return false;
        }

        /**
         * Prepara los nuevos datos y los cambia en las estadísticas.
         */
        function reloadKeycounterData() {
            //let canvas = document.getElementById('line-chart');
            let keyboard = getKeyboardData();
            let mouse = getMouseData();

            if(keyboard) {
                //
            }

            if(mouse) {
                //
            }

            // TODO → Temporal, quitar cuando se dinamice por ajax
            document.getElementById('form-filter').submit();
        }

        /**
         * Añade los eventos a los filtros para cuando cambien se recarguen los
         * datos mostrados en la gráfica.
         */
        function createFilterEvents() {
            let selectors = document.getElementsByClassName('keycounter-date-select');

            if(selectors) {
                Array.from(selectors).forEach((e) => {
                    e.addEventListener('change', reloadKeycounterData);
                });
            }
        }

        window.document.addEventListener('DOMContentLoaded', () => {
            // Añade los eventos a los selectores de filtro.
            createFilterEvents();

            // Recarga cada minuto los datos.
            //let intervalReloadKeycounterData = setInterval(reloadKeycounterData, 60000);
        });
    </script>
@endsection
