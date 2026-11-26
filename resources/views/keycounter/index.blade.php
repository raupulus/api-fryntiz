@extends('layouts.app')

@use('App\Support\Format\Figures')

@section('title', 'Keycounter y Mousecounter | Api Raupulus')
@section('description', 'Estadísticas en tiempo real de pulsaciones de teclado y ratón acumuladas por días, meses y dispositivos en Linux con Keycounter.')
@section('keywords', 'keycounter, mousecounter, teclado, Raúl Caro Pastorino, raupulus, ratón, pulsaciones de teclado, contador de pulsaciones')

@section('rs-title', 'Keycounter y Mousecounter | Contador de pulsaciones')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Estadísticas diarias y mensuales de pulsaciones de teclado y ratón registradas en Linux.')
@section('rs-image', asset('images/keycounter/social-thumbnail.jpg'))
@section('rs-url', route('keycounter.index'))
@section('rs-image-alt', 'Contador de pulsaciones de teclado y ratón por rachas')

@section('content')
    {{-- Hero --}}
    <x-page-hero image="images/heroes/keycounter.webp" min-height="min-h-[40svh]">
        <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Keycounter</h1>
        <p class="text-xl text-on-hero/90">Contador de teclas pulsadas (KeyCounter && Mousecounter)</p>
    </x-page-hero>

    {{-- Aviso de privacidad --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 mt-6">
        <x-alert type="warning" icon="privacy_tip">
            <strong>Aviso de privacidad:</strong> Los datos del día actual son aproximados y pueden mostrar ligeras
            diferencias con la realidad.
        </x-alert>
    </div>

    {{-- Filtros de fecha --}}
    <section class="py-8 bg-surface">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-4">
                <p class="text-on-surface-variant mb-1">
                    Estadísticas desde {{ $keyboard_statistics['period_start'] }}
                    hasta {{ $keyboard_statistics['period_end'] }}
                </p>
            </div>

            <div class="text-center mb-8">
                <form id="form-filter" action="{{ route('keycounter.index') }}" method="GET" class="flex flex-wrap items-end justify-center gap-4">
                    <div class="text-left">
                        <label for="keycounter-year" class="block text-xs font-semibold text-on-surface-variant mb-1">Año</label>
                        <select id="keycounter-year"
                                name="year"
                                class="keycounter-date-select border border-outline rounded-lg text-on-surface h-10 px-4 bg-surface-container-lowest">
                            @foreach(range(date('Y'), 2013) as $y)
                                <option value="{{ $y }}" {{ ($year && ($year == $y)) ? 'selected' : '' }}>
                                    Año {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="text-left">
                        <label for="keycounter-month" class="block text-xs font-semibold text-on-surface-variant mb-1">Mes</label>
                        <select id="keycounter-month"
                                name="month"
                                class="keycounter-date-select border border-outline rounded-lg text-on-surface h-10 px-4 bg-surface-container-lowest">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}"
                                    {{ ($month && ($month == $m)) ? 'selected' : '' }}
                                    {{ ($year == date('Y') && $m > date('n')) ? 'disabled' : '' }}>
                                    {{ $months[$m - 1] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <x-button type="submit" size="sm" variant="primary">
                        Aplicar periodo
                    </x-button>

                    @if((int) $year !== (int) date('Y') || (int) $month !== (int) date('n'))
                        <x-button :href="route('keycounter.index')" size="sm" variant="outline">
                            Mes actual
                        </x-button>
                    @endif
                </form>
            </div>

            {{-- Gráfico --}}
            <div class="bg-surface-container-lowest rounded-xl p-4 sm:p-6 shadow-lg mb-8">
                <div class="relative h-64 sm:h-80 w-full">
                    <canvas id="line-chart"></canvas>
                </div>
                @php
                    $chartLabels = array_values(array_filter(explode(',', (string) $labelsString), static fn (string $v): bool => $v !== ''));
                    $chartDatasets = json_decode((string) $datasetJson, true) ?? [];
                    $chartPayload = [
                        'labels' => $chartLabels,
                        'datasets' => $chartDatasets,
                        'xLabel' => 'Días en el mes: '.($month ? $months[$month - 1] : 'actual'),
                    ];
                @endphp
                <script id="keycounter-chart-data" type="application/json">@json($chartPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)</script>
            </div>

            {{-- Descripción --}}
            <div class="mb-8">
                <h2 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre esta api</h2>
                <p class="text-on-surface-variant mb-2">
                    La finalidad de esta aplicación es leer las pulsaciones de teclado y ratón quedando de forma anónima las teclas pulsadas por privacidad y transmitiendo para ser almacenado en esta API solamente las estadísticas generales por rachas.
                </p>
                <p class="text-on-surface-variant mb-2">
                    Las rachas para el contador de pulsaciones son estadísticas de teclas pulsadas hasta que pasan <strong>15</strong> segundos sin que ninguna tecla sea pulsada, se almacena el promedio de pulsaciones y calcula velocidad media además de generar una puntuación (<em>score</em>) que valora la racha mediante un algoritmo propio.
                </p>
                <p class="text-on-surface-variant mb-1">
                    La herramienta que he creado está disponible para <strong>GNU/Linux</strong> y <strong>macOS</strong>.
                    Ha sido construida utilizando python3:
                    <a href="https://gitlab.com/raupulus/python-keycounter" class="underline text-on-tertiary-container font-bold text-xs" target="_blank" rel="noopener noreferrer">https://gitlab.com/raupulus/python-keycounter</a>
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
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-center text-on-surface mb-6">Resumen del mes</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm uppercase text-on-surface-variant leading-tight">Total de rachas</p>
                    <p class="text-3xl text-on-surface font-semibold my-3">{{ Figures::asInteger($keyboard_statistics['period_count']) }}</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm uppercase text-on-surface-variant leading-tight">Total de pulsaciones</p>
                    <p class="text-3xl text-on-surface font-semibold my-3">{{ Figures::asInteger($keyboard_statistics['period_total_pulsations']) }}</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm uppercase text-on-surface-variant leading-tight">Max. Puls./disp. en 1 día</p>
                    <p class="text-3xl text-on-surface font-semibold my-3">{{ Figures::asInteger($keyboard_statistics['data']->max('total_pulsations')) }}</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm uppercase text-on-surface-variant leading-tight">Puntuación total</p>
                    <p class="text-3xl text-on-surface font-semibold my-3">{{ Figures::asInteger($monthSummary['total_score']) }}</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm uppercase text-on-surface-variant leading-tight">Pulsaciones media (por racha)</p>
                    <p class="text-3xl text-on-surface font-semibold my-3">{{ Figures::asInteger($monthSummary['avg_pulsations']) }}</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm uppercase text-on-surface-variant leading-tight">Media teclas especiales</p>
                    <p class="text-3xl text-on-surface font-semibold my-3">{{ Figures::asInteger($monthSummary['avg_special_keys']) }}</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm uppercase text-on-surface-variant leading-tight">Pulsaciones por minuto</p>
                    <p class="text-3xl text-on-surface font-semibold my-3">{{ Figures::asInteger($monthSummary['pulsations_per_minute']) }}</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm uppercase text-on-surface-variant leading-tight">Puntuación media</p>
                    <p class="text-3xl text-on-surface font-semibold my-3">{{ Figures::asInteger($monthSummary['avg_score']) }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Tarjetas resumen Keyboard y Mouse --}}
    <section class="py-8 bg-surface">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-center text-on-surface mb-6">Resumen últimos registros</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Keyboard Summary Card --}}
                <div class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-primary-container text-on-primary p-4">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-3xl" aria-hidden="true">keyboard</span>
                            <h3 class="text-xl font-bold">Teclado</h3>
                        </div>
                        <p class="text-on-primary/85 text-xs mt-1">Resumen últimos {{ $keyboardSummary['total_records'] }} registros</p>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Media de pulsaciones</span>
                            <span class="text-on-surface font-bold">{{ Figures::asInteger($keyboardSummary['avg_pulsations']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Pulsaciones/min (media)</span>
                            <span class="text-on-surface font-bold">{{ Figures::asInteger($keyboardSummary['avg_pulsations_per_minute']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Score medio</span>
                            <span class="text-on-surface font-bold">{{ Figures::asInteger($keyboardSummary['avg_score']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Media teclas especiales</span>
                            <span class="text-on-surface font-bold">{{ Figures::asInteger($keyboardSummary['avg_pulsations_special_keys']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Máximo pulsaciones en racha</span>
                            <span class="text-on-surface font-bold">{{ Figures::asInteger($keyboardSummary['max_pulsations']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Total pulsaciones</span>
                            <span class="text-on-surface font-bold text-lg">{{ Figures::asInteger($keyboardSummary['total_pulsations']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Total teclas especiales</span>
                            <span class="text-on-surface font-bold text-lg">{{ Figures::asInteger($keyboardSummary['total_pulsations_special_keys']) }}</span>
                        </div>
                        <div class="text-xs text-on-surface-variant pt-2 border-t border-surface-container">
                            Período: {{ $keyboardSummary['period_start'] }} — {{ $keyboardSummary['period_end'] }}
                        </div>
                    </div>
                </div>

                {{-- Mouse Summary Card --}}
                <div class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-tertiary text-on-primary p-4">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-3xl" aria-hidden="true">mouse</span>
                            <h3 class="text-xl font-bold">Ratón</h3>
                        </div>
                        <p class="text-on-primary/85 text-xs mt-1">Resumen últimos {{ $mouseSummary['total_records'] }} registros</p>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Media de clicks</span>
                            <span class="text-on-surface font-bold">{{ Figures::asInteger($mouseSummary['avg_clicks']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Clicks/min (media)</span>
                            <span class="text-on-surface font-bold">{{ Figures::asInteger($mouseSummary['avg_clicks_per_minute']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Máximo clicks en racha</span>
                            <span class="text-on-surface font-bold">{{ Figures::asInteger($mouseSummary['max_clicks']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant text-sm">Total clicks</span>
                            <span class="text-on-surface font-bold text-lg">{{ Figures::asInteger($mouseSummary['total_clicks']) }}</span>
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
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <h2 class="text-2xl font-bold text-on-surface mb-6">Estadísticas Globales</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                {{-- Total global --}}
                <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-xl p-4 text-center shadow">
                    <span class="material-symbols-outlined text-primary text-3xl" aria-hidden="true">functions</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Figures::abbreviated($widgets['total_global']) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.total_pulsations') }}</p>
                </div>

                {{-- Año top --}}
                @if($widgets['top_year'])
                <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-xl p-4 text-center shadow">
                    <span class="material-symbols-outlined text-primary text-3xl" aria-hidden="true">military_tech</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Figures::abbreviated($widgets['top_year']->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.best_year') }}: {{ (int)$widgets['top_year']->year }}</p>
                </div>
                @endif

                {{-- Mes top --}}
                @if($widgets['top_month'])
                <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-xl p-4 text-center shadow">
                    <span class="material-symbols-outlined text-primary text-3xl" aria-hidden="true">event</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Figures::abbreviated($widgets['top_month']->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.best_month') }}: {{ (int)$widgets['top_month']->month }}/{{ (int)$widgets['top_month']->year }}</p>
                </div>
                @endif

                {{-- Día top --}}
                @if($widgets['top_day'])
                <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-xl p-4 text-center shadow">
                    <span class="material-symbols-outlined text-tertiary text-3xl" aria-hidden="true">calendar_month</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Figures::abbreviated($widgets['top_day']->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.best_day') }}: {{ \Carbon\Carbon::parse($widgets['top_day']->day)->format('d/m/Y') }}</p>
                </div>
                @endif

                {{-- Hora top (convertida a la hora local del navegador) --}}
                @if($widgets['top_hour'])
                <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-xl p-4 text-center shadow"
                     data-top-hour-utc="{{ (int) $widgets['top_hour']->hour }}">
                    <span class="material-symbols-outlined text-tertiary text-3xl" aria-hidden="true">schedule</span>
                    <p class="text-2xl font-bold text-on-surface mt-2">{{ Figures::abbreviated($widgets['top_hour']->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.best_hour') }}: <span id="top-hour-local">--:00</span></p>
                </div>
                @endif

                {{-- Totales por año --}}
                @foreach($widgets['totals_by_year'] as $yearData)
                <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-xl p-4 text-center shadow">
                    <span class="material-symbols-outlined text-primary text-3xl" aria-hidden="true">bar_chart</span>
                    <p class="text-xl font-bold text-on-surface mt-2">{{ Figures::abbreviated($yearData->total) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('keycounter.year') }} {{ (int)$yearData->year }}</p>
                </div>
                @endforeach

                {{-- Totales por dispositivo. --}}
                @php($topDeviceId = (int) ($widgets['top_device']->hardware_device_id ?? 0))
                @foreach($widgets['totals_by_device'] as $deviceData)
                    @if((int) $deviceData->hardware_device_id === $topDeviceId)
                        <div class="bg-surface-container-lowest border border-primary/40 rounded-xl p-4 text-center shadow">
                            <span class="material-symbols-outlined text-primary text-3xl" aria-hidden="true">devices</span>
                            <p class="text-xl font-bold text-on-surface mt-2">{{ Figures::abbreviated($deviceData->total) }}</p>
                            <p class="text-xs text-on-surface-variant break-words">{{ $deviceData->name }}</p>
                            <p class="mt-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-primary-container/15 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-primary">
                                    <span class="material-symbols-outlined text-sm leading-none" aria-hidden="true">emoji_events</span>
                                    {{ __('keycounter.top_device') }}
                                </span>
                            </p>
                        </div>
                    @else
                        <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-xl p-4 text-center shadow">
                            <span class="material-symbols-outlined text-on-surface-variant text-3xl" aria-hidden="true">dns</span>
                            <p class="text-xl font-bold text-on-surface mt-2">{{ Figures::abbreviated($deviceData->total) }}</p>
                            <p class="text-xs text-on-surface-variant break-words">{{ $deviceData->name }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/keycounter-chart.js')
@endpush
