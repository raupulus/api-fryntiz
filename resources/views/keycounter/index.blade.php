@extends('layouts.app')

@section('title', 'Keycounter && Mousecounter')
@section('description', 'Contador de pulsaciones de teclado y ratón por rachas')
@section('keywords', 'keycounter, mousecounter, teclado, Raúl Caro Pastorino, raupulus, ratón, pulsaciones de teclado, pulsaciones de ratón, contador de pulsaciones')

@section('rs-title', 'Keycounter && Mousecounter, contador de pulsaciones')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Contador de pulsaciones de teclado y ratón por rachas')
@section('rs-image', asset('images/keycounter/social-thumbnail.jpg'))
@section('rs-url', route('keycounter.index'))
@section('rs-image-alt', 'Contador de pulsaciones de teclado y ratón por rachas')

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Keycounter</h1>
            <p class="text-xl text-white/80">Contador de teclas pulsadas (KeyCounter && Mousecounter)</p>
        </div>
    </section>

    {{-- Filtros de fecha --}}
    <section class="py-8 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-4">
                <p class="text-on-surface-variant mb-1">
                    Estadísticas desde {{ $keyboard_statistics['period_start'] }}
                    hasta {{ $keyboard_statistics['period_end'] }}
                </p>
            </div>

            <div class="text-center mb-8">
                <form id="form-filter" action="{{ route('keycounter.index') }}" method="GET" class="flex flex-wrap justify-center gap-4">
                    <select name="year"
                            class="keycounter-date-select border border-outline-variant rounded-lg text-on-surface h-10 pl-5 pr-10 bg-surface-container-lowest appearance-none">
                        @foreach(range(date('Y'), 2019) as $y)
                            <option value="{{ $y }}" {{ ($year && ($year == $y)) ? 'selected' : '' }}>
                                Año {{ $y }}
                            </option>
                        @endforeach
                    </select>

                    <select name="month"
                            class="keycounter-date-select border border-outline-variant rounded-lg text-on-surface h-10 pl-5 pr-10 bg-surface-container-lowest appearance-none">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ ($month && ($month == $m)) ? 'selected' : '' }}>
                                {{ $months[$m - 1] }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Gráfico --}}
            <div class="bg-surface-container-lowest rounded-xl p-6 shadow-lg mb-8">
                <canvas id="line-chart" width="600" height="350" style="max-height: 400px; max-width: 100%; margin: auto"></canvas>
            </div>

            {{-- Aviso temporal --}}
            <div class="bg-error-container border border-error/30 mb-5 text-on-surface px-4 py-3 rounded-lg" role="alert">
                <span class="text-sm">
                    Sitio <strong>temporal</strong> con la finalidad de detectar posibles caídas/cuelgues o lecturas imprecisas en los programas que desarrollo para obtener las estadísticas de pulsaciones de teclado y ratón que conforman este <strong>keycounter</strong>.
                    <br/>
                    Una vez acabada la aplicación, este sitio desaparecerá quedando solo como una api privada accesible desde un componente en Vue.js para mi sitio web personal:
                    <a href="https://raupulus.dev" class="underline text-on-tertiary-container font-bold text-xs" target="_blank">https://raupulus.dev</a>
                </span>
            </div>

            {{-- Descripción --}}
            <div class="mb-8">
                <h3 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre esta api</h3>
                <p class="text-on-surface-variant mb-2">
                    Los datos pueden no ser precisos debido a que aún se encuentra en depuración para detección de errores en código o cálculos.
                </p>
                <p class="text-on-surface-variant mb-2">
                    La finalidad de esta aplicación es leer las pulsaciones de teclado y ratón quedando de forma anónima las teclas pulsadas por privacidad y transmitiendo para ser almacenado en esta API solamente las estadísticas generales por rachas.
                </p>
                <p class="text-on-surface-variant mb-2">
                    Las rachas para el contador de pulsaciones son estadísticas de teclas pulsadas hasta que pasan <strong>15</strong> segundos sin que ninguna tecla sea pulsada, se almacena el promedio de pulsaciones y calcula velocidad media además de generar una puntuación (<em>score</em>) que valora la racha mediante un algoritmo propio.
                </p>
                <p class="text-on-surface-variant mb-1">
                    La herramienta que he creado está sólo disponible para sistemas <strong>GNU/LINUX</strong>.
                    Ha sido construida utilizando python3:
                    <a href="https://gitlab.com/raupulus/python-keycounter" class="underline text-on-tertiary-container font-bold text-xs" target="_blank">https://gitlab.com/raupulus/python-keycounter</a>
                </p>
                <div class="bg-surface-container border border-outline-variant/30 my-4 px-4 py-2 rounded-lg text-center">
                    <span class="font-bold text-on-surface">Franja horaria UTC +0:00</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Resumen del mes --}}
    <section class="py-8 bg-surface-container-low">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-on-surface mb-6">Resumen del mes</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Total de rachas</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ $keyboard_statistics['period_count'] }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Total de puntuaciones</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ $keyboard_statistics['period_total_pulsations'] }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Max. Puls./disp. en 1 día</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ $keyboard_statistics['data']->max('total_pulsations') }}</h3>
                </div>
            </div>
        </div>
    </section>

    {{-- Resumen últimos 100 resultados --}}
    <section class="py-8 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-on-surface mb-6">Resumen últimos 100 resultados</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant">Total de pulsaciones</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ $keyboard->sum('pulsations') }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant">Puntuación Total</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ $keyboard->sum('score') }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant">Pulsaciones media</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ round($keyboard->avg('pulsations'), 2) }}</h3>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant">Media teclas especiales</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ round($keyboard->avg('pulsations_special_keys'), 2) }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant">Pulsaciones por minuto</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ round($keyboard->avg('pulsation_average'), 2) }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant">Puntuación Media</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ round($keyboard->avg('score'), 2) }}</h3>
                </div>
            </div>
        </div>
    </section>

    {{-- Tabla Keyboard --}}
    <section class="py-8 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-6 overflow-x-auto">
            <h2 class="text-2xl font-bold text-on-surface mb-4">KEYBOARD</h2>
            <table class="min-w-max w-full table-auto text-sm">
                <thead>
                    <tr class="bg-inverse-surface text-inverse-on-surface">
                        <td class="px-2 py-2 text-center">nº</td>
                        <td class="px-2 py-2 text-center">start_at</td>
                        <td class="px-2 py-2 text-center">end_at</td>
                        <td class="px-2 py-2 text-center">duration</td>
                        <td class="px-2 py-2 text-center">pulsations</td>
                        <td class="px-2 py-2 text-center">special_keys</td>
                        <td class="px-2 py-2 text-center">avg</td>
                        <td class="px-2 py-2 text-center">score</td>
                        <td class="px-2 py-2 text-center">weekday</td>
                        <td class="px-2 py-2 text-center">device_id</td>
                        <td class="px-2 py-2 text-center">device_name</td>
                        <td class="px-2 py-2 text-center">created_at</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach($keyboard as $reg)
                        <tr class="bg-surface-container-lowest border-b border-outline-variant/20">
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->id }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->start_at }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->end_at }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->duration }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->pulsations }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->pulsations_special_keys }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->pulsation_average }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->score }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->weekday }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->hardware_device_id }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->hardware->name ?? '-' }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->created_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Tabla Mouse --}}
    <section class="py-8 bg-surface">
        <div class="max-w-7xl mx-auto px-6 overflow-x-auto">
            <h2 class="text-2xl font-bold text-on-surface mb-4">MOUSE</h2>
            <table class="min-w-max w-full table-auto text-sm">
                <thead>
                    <tr class="bg-inverse-surface text-inverse-on-surface">
                        <td class="px-2 py-2 text-center">nº</td>
                        <td class="px-2 py-2 text-center">start_at</td>
                        <td class="px-2 py-2 text-center">end_at</td>
                        <td class="px-2 py-2 text-center">duration</td>
                        <td class="px-2 py-2 text-center">clicks_left</td>
                        <td class="px-2 py-2 text-center">clicks_right</td>
                        <td class="px-2 py-2 text-center">clicks_middle</td>
                        <td class="px-2 py-2 text-center">total_clicks</td>
                        <td class="px-2 py-2 text-center">clicks_average</td>
                        <td class="px-2 py-2 text-center">weekday</td>
                        <td class="px-2 py-2 text-center">device_id</td>
                        <td class="px-2 py-2 text-center">device_name</td>
                        <td class="px-2 py-2 text-center">created_at</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mouse as $reg)
                        <tr class="bg-surface-container-lowest border-b border-outline-variant/20">
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->id }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->start_at }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->end_at }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->duration }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->clicks_left }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->clicks_right }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->clicks_middle }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->total_clicks }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->clicks_average }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->weekday }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->device_id ?? $reg->hardware_device_id }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->hardware->name ?? '-' }}</td>
                            <td class="px-2 py-2 text-center text-on-surface">{{ $reg->created_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js"></script>
    <script>
        var labels = "{{ $labelsString }}".split(',');
        var datasetJsonString = '{!! $datasetJson !!}';
        var dataset = JSON.parse(datasetJsonString);

        var chartjs = new Chart(document.getElementById("line-chart"), {
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
                scales: {
                    yAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: 'Pulsaciones Totales'
                        }
                    }],
                    xAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: 'Días en el mes: {{ $month ? $months[$month-1] : "actual" }}'
                        }
                    }],
                }
            }
        });

        function reloadKeycounterData() {
            document.getElementById('form-filter').submit();
        }

        function createFilterEvents() {
            let selectors = document.getElementsByClassName('keycounter-date-select');
            if (selectors) {
                Array.from(selectors).forEach((e) => {
                    e.addEventListener('change', reloadKeycounterData);
                });
            }
        }

        window.document.addEventListener('DOMContentLoaded', () => {
            createFilterEvents();
        });
    </script>
@endpush
