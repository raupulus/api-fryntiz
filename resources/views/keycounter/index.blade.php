@extends('layouts.app')

@use('App\Support\Format\Cifra')

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

    {{-- Aviso de privacidad --}}
    <div class="max-w-5xl mx-auto px-6 mt-6">
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-700 rounded-lg p-4 text-sm text-amber-800 dark:text-amber-200" role="alert">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-amber-500 mt-0.5">privacy_tip</span>
                <p>
                    <strong>Aviso de privacidad:</strong> Por motivos de privacidad, los datos mostrados pueden tener pequeñas variaciones
                    en las representaciones. Los datos del día actual no serán exactos, por elección del desarrollador, para proteger
                    información precisa sobre actividad en tiempo real.
                </p>
            </div>
        </div>
    </div>

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
                        @foreach(range(date('Y'), 2013) as $y)
                            <option value="{{ $y }}" {{ ($year && ($year == $y)) ? 'selected' : '' }}>
                                Año {{ $y }}
                            </option>
                        @endforeach
                    </select>

                    <select name="month"
                            class="keycounter-date-select border border-outline-variant rounded-lg text-on-surface h-10 pl-5 pr-10 bg-surface-container-lowest appearance-none">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}"
                                {{ ($month && ($month == $m)) ? 'selected' : '' }}
                                {{ ($year == date('Y') && $m > date('n')) ? 'disabled' : '' }}>
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

            {{-- Descripción --}}
            <div class="mb-8">
                <h3 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre esta api</h3>
                <p class="text-on-surface-variant mb-2">
                    La finalidad de esta aplicación es leer las pulsaciones de teclado y ratón quedando de forma anónima las teclas pulsadas por privacidad y transmitiendo para ser almacenado en esta API solamente las estadísticas generales por rachas.
                </p>
                <p class="text-on-surface-variant mb-2">
                    Las rachas para el contador de pulsaciones son estadísticas de teclas pulsadas hasta que pasan <strong>15</strong> segundos sin que ninguna tecla sea pulsada, se almacena el promedio de pulsaciones y calcula velocidad media además de generar una puntuación (<em>score</em>) que valora la racha mediante un algoritmo propio.
                </p>
                <p class="text-on-surface-variant mb-1">
                    La herramienta que he creado está disponible para <strong>GNU/Linux</strong> y <strong>macOS</strong>.
                    Ha sido construida utilizando python3:
                    <a href="https://gitlab.com/raupulus/python-keycounter" class="underline text-on-tertiary-container font-bold text-xs" target="_blank">https://gitlab.com/raupulus/python-keycounter</a>
                </p>
                <p class="text-on-surface-variant mb-1">
                    Para macOS hay además <strong>MacOs KeyCounter</strong>, una aplicación complementaria que
                    muestra las estadísticas en la barra superior del sistema. Está en ese mismo repositorio.
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
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Total de rachas</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ Cifra::entera($keyboard_statistics['period_count']) }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Total de pulsaciones</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ Cifra::entera($keyboard_statistics['period_total_pulsations']) }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Max. Puls./disp. en 1 día</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ Cifra::entera($keyboard_statistics['data']->max('total_pulsations')) }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Puntuación total</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ Cifra::entera($monthSummary['total_score']) }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Pulsaciones media (por racha)</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ Cifra::entera($monthSummary['avg_pulsations']) }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Media teclas especiales</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ Cifra::entera($monthSummary['avg_special_keys']) }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Pulsaciones por minuto</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ Cifra::entera($monthSummary['pulsations_per_minute']) }}</h3>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <h4 class="text-sm uppercase text-on-surface-variant leading-tight">Puntuación media</h4>
                    <h3 class="text-3xl text-on-surface font-semibold my-3">{{ Cifra::entera($monthSummary['avg_score']) }}</h3>
                </div>
            </div>
        </div>
    </section>

    {{-- Tarjetas resumen Keyboard y Mouse --}}
    <section class="py-8 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-on-surface mb-6">Resumen últimos registros</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Keyboard Summary Card --}}
                <div class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 p-4">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-white text-3xl">keyboard</span>
                            <h3 class="text-xl font-bold text-white">Teclado</h3>
                        </div>
                        <p class="text-indigo-200 text-xs mt-1">Resumen últimos {{ $keyboardSummary['total_records'] }} registros</p>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Media de pulsaciones</span>
                            <span class="text-on-surface font-bold">{{ Cifra::entera($keyboardSummary['avg_pulsations']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Pulsaciones/min (media)</span>
                            <span class="text-on-surface font-bold">{{ Cifra::entera($keyboardSummary['avg_pulsations_per_minute']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Score medio</span>
                            <span class="text-on-surface font-bold">{{ Cifra::entera($keyboardSummary['avg_score']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Media teclas especiales</span>
                            <span class="text-on-surface font-bold">{{ Cifra::entera($keyboardSummary['avg_pulsations_special_keys']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Máximo pulsaciones en racha</span>
                            <span class="text-on-surface font-bold">{{ Cifra::entera($keyboardSummary['max_pulsations']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Total pulsaciones</span>
                            <span class="text-on-surface font-bold text-lg">{{ Cifra::entera($keyboardSummary['total_pulsations']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Total teclas especiales</span>
                            <span class="text-on-surface font-bold text-lg">{{ Cifra::entera($keyboardSummary['total_pulsations_special_keys']) }}</span>
                        </div>
                        <div class="text-xs text-on-surface-variant pt-2 border-t border-surface-container">
                            Período: {{ $keyboardSummary['period_start'] }} — {{ $keyboardSummary['period_end'] }}
                        </div>
                    </div>
                </div>

                {{-- Mouse Summary Card --}}
                <div class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 p-4">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-white text-3xl">mouse</span>
                            <h3 class="text-xl font-bold text-white">Ratón</h3>
                        </div>
                        <p class="text-emerald-200 text-xs mt-1">Resumen últimos {{ $mouseSummary['total_records'] }} registros</p>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Media de clicks</span>
                            <span class="text-on-surface font-bold">{{ Cifra::entera($mouseSummary['avg_clicks']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Clicks/min (media)</span>
                            <span class="text-on-surface font-bold">{{ Cifra::entera($mouseSummary['avg_clicks_per_minute']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Máximo clicks en racha</span>
                            <span class="text-on-surface font-bold">{{ Cifra::entera($mouseSummary['max_clicks']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Total clicks</span>
                            <span class="text-on-surface font-bold text-lg">{{ Cifra::entera($mouseSummary['total_clicks']) }}</span>
                        </div>
                        <div class="text-xs text-on-surface-variant pt-2 border-t border-surface-container">
                            Período: {{ $mouseSummary['period_start'] }} — {{ $mouseSummary['period_end'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Widgets estadísticos --}}
    <section class="py-8 bg-surface-container-low">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-2xl font-bold text-on-surface mb-6">Estadísticas Globales</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                {{-- Total global --}}
                <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-center shadow">
                    <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-3xl">functions</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Cifra::miles($widgets['total_global']) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.total_pulsations') }}</p>
                </div>

                {{-- Año top --}}
                @if($widgets['top_year'])
                <div class="bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 text-center shadow">
                    <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-3xl">military_tech</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Cifra::miles($widgets['top_year']->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.best_year') }}: {{ (int)$widgets['top_year']->year }}</p>
                </div>
                @endif

                {{-- Mes top --}}
                @if($widgets['top_month'])
                <div class="bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800 rounded-lg p-4 text-center shadow">
                    <span class="material-symbols-outlined text-orange-600 dark:text-orange-400 text-3xl">event</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Cifra::miles($widgets['top_month']->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.best_month') }}: {{ (int)$widgets['top_month']->month }}/{{ (int)$widgets['top_month']->year }}</p>
                </div>
                @endif

                {{-- Día top --}}
                @if($widgets['top_day'])
                <div class="bg-lime-50 dark:bg-lime-950/30 border border-lime-200 dark:border-lime-800 rounded-lg p-4 text-center shadow">
                    <span class="material-symbols-outlined text-lime-600 dark:text-lime-400 text-3xl">calendar_month</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Cifra::miles($widgets['top_day']->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.best_day') }}: {{ \Carbon\Carbon::parse($widgets['top_day']->day)->format('d/m/Y') }}</p>
                </div>
                @endif

                {{-- Hora top (convertida a la hora local del navegador) --}}
                @if($widgets['top_hour'])
                <div class="bg-cyan-50 dark:bg-cyan-950/30 border border-cyan-200 dark:border-cyan-800 rounded-lg p-4 text-center shadow"
                     data-top-hour-utc="{{ (int) $widgets['top_hour']->hour }}">
                    <span class="material-symbols-outlined text-cyan-600 dark:text-cyan-400 text-3xl">schedule</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Cifra::miles($widgets['top_hour']->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.best_hour') }}: <span id="top-hour-local">--:00</span></p>
                </div>
                @endif

                {{-- Totales por año --}}
                @foreach($widgets['totals_by_year'] as $yearData)
                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-center shadow">
                    <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-3xl">bar_chart</span>
                    <p class="text-xl font-bold text-on-surface mt-2">{{ Cifra::miles($yearData->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.year') }} {{ (int)$yearData->year }}</p>
                </div>
                @endforeach

                {{-- Totales por dispositivo.

                     El de más pulsaciones se pinta destacado y con el
                     distintivo «Dispositivo top» en lugar de repetirse: antes
                     salía también en una tarjeta propia, así que el mismo
                     equipo aparecía dos veces y dejaba una tarjeta suelta al
                     final de la rejilla. --}}
                @php($topDeviceId = (int) ($widgets['top_device']->hardware_device_id ?? 0))
                @foreach($widgets['totals_by_device'] as $deviceData)
                    @if((int) $deviceData->hardware_device_id === $topDeviceId)
                        <div class="bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800 rounded-lg p-4 text-center shadow">
                            <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-3xl">devices</span>
                            <p class="text-xl font-bold text-on-surface mt-2">{{ Cifra::miles($deviceData->total) }}</p>
                            <p class="text-xs text-on-surface-variant">{{ $deviceData->name }}</p>
                            <p class="mt-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 dark:bg-purple-900/50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-purple-700 dark:text-purple-300">
                                    <span class="material-symbols-outlined text-sm leading-none">emoji_events</span>
                                    {{ __('keycounter.top_device') }}
                                </span>
                            </p>
                        </div>
                    @else
                        <div class="bg-teal-50 dark:bg-teal-950/30 border border-teal-200 dark:border-teal-800 rounded-lg p-4 text-center shadow">
                            <span class="material-symbols-outlined text-teal-600 dark:text-teal-400 text-3xl">dns</span>
                            <p class="text-xl font-bold text-on-surface mt-2">{{ Cifra::miles($deviceData->total) }}</p>
                            <p class="text-xs text-on-surface-variant">{{ $deviceData->name }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
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

        // Deshabilitar meses futuros dinámicamente al cambiar año
        document.querySelector('select[name="year"]').addEventListener('change', function() {
            const selectedYear = parseInt(this.value);
            const currentYear = new Date().getFullYear();
            const currentMonth = new Date().getMonth() + 1;
            const monthSelect = document.querySelector('select[name="month"]');

            Array.from(monthSelect.options).forEach(option => {
                const monthVal = parseInt(option.value);
                option.disabled = (selectedYear === currentYear && monthVal > currentMonth);
            });

            if (selectedYear === currentYear && parseInt(monthSelect.value) > currentMonth) {
                monthSelect.value = currentMonth;
            }
        });

        // Los datos se almacenan en UTC+0; la tarjeta "Mejor hora" se
        // recalcula aquí a la zona horaria del propio navegador.
        function localizeTopHour() {
            var el = document.querySelector('[data-top-hour-utc]');
            var target = document.getElementById('top-hour-local');
            if (!el || !target) {
                return;
            }

            var utcHour = parseInt(el.getAttribute('data-top-hour-utc'), 10);
            var localDate = new Date(Date.UTC(2000, 0, 1, utcHour, 0, 0));
            var localHour = String(localDate.getHours()).padStart(2, '0');

            target.textContent = localHour + ':00';
        }

        window.document.addEventListener('DOMContentLoaded', () => {
            createFilterEvents();
            localizeTopHour();
        });
    </script>
@endpush
