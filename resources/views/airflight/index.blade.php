@extends('layouts.app')

@section('title', 'Vuelos en Tiempo Real Chipiona | Api Raupulus')
@section('description', 'Seguimiento en tiempo real de vuelos y aeronaves ADS-B recibidos sobre Chipiona y la bahía de Cádiz con mapa interactivo OpenLayers y estadísticas.')
@section('keywords', 'vuelos, aviones, chipiona, Raúl Caro Pastorino, raupulus, airplanes, airflight, ads-b, 1090mhz')

@section('rs-title', 'Vuelos en Tiempo Real sobre Chipiona | Api Raupulus')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Registro y mapa de vuelos ADS-B en tiempo real sobre Chipiona y alrededores.')
@section('rs-image', asset('images/airflight/social-thumbnail.jpg'))
@section('rs-url', route('airflight.index'))
@section('rs-image-alt', 'Vuelos en tiempo real sobre Chipiona')

@section('content')
    {{-- Hero --}}
    <x-page-hero image="images/heroes/airflight.webp" min-height="min-h-[40svh]">
        <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Airflight</h1>
        <p class="text-xl text-on-hero/90">Registros de vuelos en tiempo real — Chipiona</p>
    </x-page-hero>

    {{-- Descripción --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <x-alert type="warning" class="mb-5">
                Sitio <strong>temporal</strong> con la finalidad de comprobar el funcionamiento de los dispositivos que uso para recopilar los datos (raspberry pi y capturadora de televisión digital con una antena modificada para 1090 MHz)
            </x-alert>

            <h2 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre esta api</h2>
            <p class="text-on-surface-variant mb-2">
                Básicamente monitorizo la señal que emiten los aviones enviando su posición para no chocarse entre ellos obteniendo esos datos por ondas que decodifico y parseo para poder sacar estadísticas y trazar rutas de vuelos en tiempo real.
            </p>
            <p class="text-on-surface-variant mb-2">
                Los datos pueden no ser precisos debido a que aún se encuentra en depuración mientras voy detectando posibles problemas en el hardware que he manipulado y el software que le he construido.
            </p>
            <p class="text-on-surface-variant mb-2">
                Puedes ver el desarrollo del programa:
                <a href="https://gitlab.com/raupulus/dump1090-to-db" class="underline text-primary font-bold text-sm" target="_blank" rel="noopener noreferrer">
                    https://gitlab.com/raupulus/dump1090-to-db
                </a>
            </p>
            <div class="bg-surface-container border border-outline-variant/30 my-4 px-4 py-2 rounded-lg text-center">
                <span class="font-bold text-on-surface">
                    Horas mostradas en tu zona horaria (<span id="detected-timezone">detectando…</span>)
                </span>
            </div>
        </div>
    </section>

    {{-- Mapa interactivo de vuelos --}}
    <section class="py-8 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-4 text-center">Aviones en Tiempo Real</h2>
            <p class="text-sm text-center text-on-surface-variant mb-6">
                (Selecciona el avión en el mapa o la tabla para ver el recorrido que realiza.)
            </p>

            <div id="box-airflight"
                 data-aircrafts-url="{{ route('airflight.aircrafts') }}"
                 data-receiver-url="{{ route('airflight.receiver') }}"
                 class="relative w-full bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden scroll-mt-24 border border-outline-variant/20">
                <div class="p-3 bg-surface-container flex justify-end border-b border-outline-variant/20">
                    <x-button type="button" size="sm" variant="outline" class="btn-downto-map">
                        Ir debajo del Mapa
                    </x-button>
                </div>

                {{-- Mapa OpenLayers moderno --}}
                <div id="map_container" class="relative w-full">
                    <div id="loader" hidden class="absolute top-3 right-3 z-10 rounded-full bg-surface-container-lowest/90 px-3 py-1 text-xs font-medium text-on-surface shadow">
                        Actualizando radar…
                    </div>
                    <div id="map_canvas" class="h-[440px] lg:h-[540px] w-full bg-surface-container"></div>
                </div>

                {{-- Panel de telemetría y tabla en vivo --}}
                <div id="sidebar_container" class="p-4 sm:p-6 space-y-4">
                    <div id="sidebar_canvas" class="space-y-4">
                        <div id="sudo_buttons" class="flex flex-wrap gap-2">
                            <x-button type="button" size="sm" variant="outline" class="btn-reset-map">Reiniciar Zoom</x-button>
                            <x-button type="button" size="sm" variant="outline" class="btn-upto-map">Ir encima del Mapa</x-button>
                            <x-button type="button" size="sm" variant="primary" class="btn-reload-map">Recargar datos</x-button>
                        </div>

                        <div id="dump1090_infoblock" class="rounded-lg bg-surface-container p-4 text-sm text-on-surface">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <strong id="infoblock_name">Estado del receptor ADS-B</strong>
                                <a href="{{ route('airflight.index') }}" id="dump1090_version" class="text-xs underline text-on-tertiary-container font-semibold">Airflight v2</a>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-on-surface-variant">
                                <div>Aviones activos: <strong id="dump1090_total_ac" class="text-on-surface">—</strong></div>
                                <div>Con posición GPS: <strong id="dump1090_total_ac_positions" class="text-on-surface">—</strong></div>
                                <div>Puntos de ruta: <strong id="dump1090_total_history" class="text-on-surface">—</strong></div>
                            </div>
                        </div>

                        <div id="selected_infoblock" hidden class="rounded-lg border border-outline-variant/30 bg-surface-container-low p-4 text-sm text-on-surface space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" id="selected_callsign" class="map-selected-callsign font-bold text-primary cursor-pointer hover:underline">—</button>
                                    <button type="button" id="selected_follow" class="map-selected-follow text-xs px-2 py-0.5 rounded bg-surface-container text-on-surface-variant cursor-pointer" title="Seguir aeronave">Seguir &#x21D2;</button>
                                    <span id="selected_registration" class="text-xs font-mono text-on-surface-variant"></span>
                                    <span id="selected_icaotype" class="text-xs text-on-surface-variant"></span>
                                    <span id="selected_emergency" class="text-xs font-bold text-error"></span>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <a id="selected_flightaware_link" target="_blank" rel="noopener noreferrer" class="underline text-on-tertiary-container">[FlightAware]</a>
                                    <a id="selected_fr24_link" target="_blank" rel="noopener noreferrer" class="underline text-on-tertiary-container">[FR24]</a>
                                    <a id="selected_flightstats_link" target="_blank" rel="noopener noreferrer" class="underline text-on-tertiary-container">[FlightStats]</a>
                                    <a id="selected_planefinder_link" target="_blank" rel="noopener noreferrer" class="underline text-on-tertiary-container">[PlaneFinder]</a>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-on-surface-variant">
                                <div>País: <strong id="selected_country" class="text-on-surface">—</strong></div>
                                <div>Altitud: <strong id="selected_altitude" class="text-on-surface">—</strong></div>
                                <div>Squawk: <strong id="selected_squawk" class="text-on-surface">—</strong></div>
                                <div>Velocidad: <strong id="selected_speed" class="text-on-surface">—</strong></div>
                                <div>RSSI: <strong id="selected_rssi" class="text-on-surface">—</strong></div>
                                <div>Rumbo: <strong id="selected_track" class="text-on-surface">—</strong></div>
                                <div>Visto hace: <strong id="selected_seen" class="text-on-surface">—</strong></div>
                                <div>Distancia: <strong id="selected_sitedist" class="text-on-surface">—</strong></div>
                                <div class="col-span-2 sm:col-span-4">Posición: <strong id="selected_position" class="text-on-surface font-mono">—</strong></div>
                            </div>
                        </div>

                        <div id="planes_table" class="overflow-x-auto rounded-lg border border-outline-variant/25">
                            <table id="tableinfo" class="w-full text-xs text-left text-on-surface">
                                <caption class="sr-only">Aeronaves activas en el radar</caption>
                                <thead class="bg-surface-container text-on-surface-variant uppercase">
                                <tr>
                                    <th scope="col" data-sort-key="icao" class="px-2.5 py-2 cursor-pointer">ICAO</th>
                                    <th scope="col" data-sort-key="flight" class="px-2.5 py-2 cursor-pointer">Vuelo</th>
                                    <th scope="col" data-sort-key="squawk" class="px-2.5 py-2 cursor-pointer">Squawk</th>
                                    <th scope="col" data-sort-key="altitude" class="px-2.5 py-2 cursor-pointer">Altitud ft</th>
                                    <th scope="col" data-sort-key="speed" class="px-2.5 py-2 cursor-pointer">Velocidad kt</th>
                                    <th scope="col" data-sort-key="distance" class="px-2.5 py-2 cursor-pointer">Distancia nm</th>
                                    <th scope="col" data-sort-key="track" class="px-2.5 py-2 cursor-pointer">Rumbo</th>
                                    <th scope="col" data-sort-key="msgs" class="px-2.5 py-2 cursor-pointer">Msgs</th>
                                    <th scope="col" data-sort-key="seen" class="px-2.5 py-2 cursor-pointer">Visto s</th>
                                </tr>
                                </thead>
                                <tbody id="radar-planes-tbody">
                                    <tr>
                                        <td colspan="9" class="px-3 py-4 text-center text-on-surface-variant">Cargando radar en tiempo real…</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="SpecialSquawkWarning" hidden class="mx-4 mb-4 rounded-lg bg-warning-container p-3 text-xs text-on-warning-container">
                    <strong>Squawk 7x00 detectado.</strong> Puede corresponder a emergencia o error de decodificación.
                </div>
                <div id="update_error" hidden class="mx-4 mb-4 rounded-lg bg-error-container p-3 text-xs text-on-error-container">
                    <strong>Problema sincronizando datos:</strong>
                    <span id="update_error_detail"></span>
                </div>
            </div>
        </div>
    </section>

    {{-- Tabla de aviones detectados.

         Se pinta ya con lo que ha resuelto el backend (sin petición extra al
         cargar). Solo cuando la página termina de cargar y estamos viendo la
         página 1 de la paginación arranca el sondeo cada minuto contra
         `airflight.detected` (ruta web, no API) que refresca esta misma
         tabla. Si el usuario está navegando otra página, el sondeo no se
         activa para no pisarle la vista. --}}
    <section class="py-12 bg-surface"
             id="detected-planes-section"
             data-url="{{ route('airflight.detected') }}"
             data-page="{{ $planes->currentPage() }}">
        <div class="max-w-7xl mx-auto px-6">
            {{-- Bloque 1: Métricas de actividad temporal (1h, 24h, 7d, Total) --}}
            <div class="mb-10">
                <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">radar</span>
                    <span>Actividad de detección</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Última hora --}}
                    <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/15 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Última hora</span>
                            <span class="p-2 rounded-lg bg-primary/10 text-primary">
                                <span class="material-symbols-outlined text-xl" aria-hidden="true">schedule</span>
                            </span>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold text-on-surface tracking-tight" id="stat-last-hour">
                                {{ number_format($stats['last_hour'] ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-xs text-on-surface-variant mt-1 flex items-center gap-1.5">
                                <span class="relative flex h-2 w-2" aria-hidden="true">
                                    <span class="motion-safe:animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-success"></span>
                                </span>
                                <span>Aeronaves en tiempo real</span>
                            </p>
                        </div>
                    </div>

                    {{-- Últimas 24 horas --}}
                    <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/15 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Últimas 24 horas</span>
                            <span class="p-2 rounded-lg bg-primary/10 text-primary">
                                <span class="material-symbols-outlined text-xl" aria-hidden="true">today</span>
                            </span>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold text-on-surface tracking-tight">
                                {{ number_format($stats['last_24h'] ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-xs text-on-surface-variant mt-1">Aeronaves en el día</p>
                        </div>
                    </div>

                    {{-- Últimos 7 días --}}
                    <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/15 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Últimos 7 días</span>
                            <span class="p-2 rounded-lg bg-primary/10 text-primary">
                                <span class="material-symbols-outlined text-xl" aria-hidden="true">date_range</span>
                            </span>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold text-on-surface tracking-tight">
                                {{ number_format($stats['last_7d'] ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-xs text-on-surface-variant mt-1">Aeronaves en la semana</p>
                        </div>
                    </div>

                    {{-- Total histórico --}}
                    <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/15 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Total histórico</span>
                            <span class="p-2 rounded-lg bg-primary/10 text-primary">
                                <span class="material-symbols-outlined text-xl" aria-hidden="true">flight</span>
                            </span>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold text-on-surface tracking-tight">
                                {{ number_format($stats['total'] ?? 0, 0, ',', '.') }}
                            </div>
                            <p class="text-xs text-on-surface-variant mt-1">Aeronaves catalogadas</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bloque 2: Aviones más vistos (Top 4 por días distintos detectado) --}}
            <div class="mb-12">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-base" aria-hidden="true">military_tech</span>
                            <span>Aviones más frecuentes</span>
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Aeronaves detectadas durante más jornadas diferentes en el receptor</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @forelse($topPlanes as $index => $topPlane)
                        <div class="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/15 shadow-xs flex flex-col justify-between relative overflow-hidden group hover:border-primary/40 transition-colors">
                            {{-- Cabecera con bandera, matrícula y ranking --}}
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    @if($topPlane->url_flag)
                                        <img src="{{ $topPlane->url_flag }}" alt="{{ $topPlane->country ?? 'Bandera' }}" class="h-4 w-6 object-cover rounded shadow-xs">
                                    @endif
                                    <span class="text-base font-bold text-on-surface font-mono tracking-tight">
                                        {{ $topPlane->registration ?: $topPlane->icao }}
                                    </span>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-primary/10 text-primary">
                                    #{{ $index + 1 }}
                                </span>
                            </div>

                            <div class="space-y-2 my-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-on-surface-variant">Modelo</span>
                                    <span class="font-medium text-on-surface">
                                        {{ $topPlane->aircraft_type ?: ($topPlane->aircraft_desc ?: 'Aeronave') }}
                                    </span>
                                </div>
                                @if($topPlane->country)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-on-surface-variant">País</span>
                                        <span class="font-medium text-on-surface truncate max-w-[140px] text-right">{{ $topPlane->country }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-on-surface-variant">Presencia</span>
                                    <span class="font-bold text-primary">{{ $topPlane->distinct_days }} días</span>
                                </div>
                                @if(isset($topPlane->total_routes) && $topPlane->total_routes > 0)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-on-surface-variant">Registros</span>
                                        <span class="text-on-surface-variant font-mono">{{ number_format($topPlane->total_routes, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-outline-variant/10 mt-2 flex items-center justify-between text-xs">
                                <span class="text-[11px] text-on-surface-variant">
                                    @if($topPlane->seen_last_at)
                                        Visto {{ \Carbon\Carbon::parse($topPlane->seen_last_at)->diffForHumans() }}
                                    @endif
                                </span>
                                <a href="https://www.flightradar24.com/data/aircraft/{{ strtolower($topPlane->registration ?: $topPlane->icao) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Ver ficha en FlightRadar24 (sitio externo)"
                                   class="text-primary hover:underline inline-flex items-center gap-0.5 font-medium">
                                    <span>FR24</span>
                                    <span class="material-symbols-outlined text-[13px]" aria-hidden="true">open_in_new</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full bg-surface-container rounded-xl p-6 text-center text-sm text-on-surface-variant">
                            No hay suficientes datos históricos de aeronaves para calcular el ranking.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-6">
                <h2 class="text-3xl font-bold text-on-surface">Aviones detectados (última hora)</h2>
                <p id="detected-planes-status" role="status" aria-live="polite" class="text-xs text-on-surface-variant"></p>
            </div>

            <div id="detected-planes-table-wrap" class="overflow-x-auto" @if($planes->count() === 0) hidden @endif>
                <table class="min-w-max w-full table-auto text-sm">
                    <caption class="sr-only">Aeronaves detectadas en la última hora</caption>
                    <thead>
                        <tr class="bg-inverse-surface text-inverse-on-surface">
                            <th scope="col" class="px-3 py-2 text-center font-semibold">ICAO</th>
                            <th scope="col" class="px-3 py-2 text-center font-semibold">Squawk</th>
                            <th scope="col" class="px-3 py-2 text-center font-semibold">Vuelo</th>
                            <th scope="col" class="px-3 py-2 text-center font-semibold">Altitud (m)</th>
                            <th scope="col" class="px-3 py-2 text-center font-semibold">Velocidad (km/h)</th>
                            <th scope="col" class="px-3 py-2 text-center font-semibold">Dirección</th>
                            <th scope="col" class="px-3 py-2 text-center font-semibold">Visto última vez</th>
                        </tr>
                    </thead>
                    <tbody id="detected-planes-tbody">
                        @foreach($planes as $plane)
                            <tr class="bg-surface-container-lowest border-b border-outline-variant/20">
                                <td class="px-3 py-2 text-center text-on-surface font-mono">{{ $plane->icao ?? '-' }}</td>
                                <td class="px-3 py-2 text-center text-on-surface">{{ $plane->squawk ?? '-' }}</td>
                                <td class="px-3 py-2 text-center text-on-surface font-bold">{{ $plane->flight ?? '-' }}</td>
                                <td class="px-3 py-2 text-center text-on-surface">{{ $plane->altitude ?? '-' }}</td>
                                <td class="px-3 py-2 text-center text-on-surface">{{ $plane->speed ?? '-' }}</td>
                                <td class="px-3 py-2 text-center text-on-surface">
                                    @if($plane->track !== null)
                                        <span class="material-symbols-outlined text-base align-middle" style="transform: rotate({{ $plane->track }}deg);" aria-hidden="true">navigation</span>
                                    @endif
                                    {{ $plane->track !== null ? $plane->track.'°' : '-' }}
                                </td>
                                <td class="px-3 py-2 text-center text-on-surface" data-seen-at="{{ $plane->seen_last_at }}">{{ $plane->seen_last_at ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="detected-planes-pagination" class="mt-6">
                {{ $planes->links() }}
            </div>

            <div id="detected-planes-empty" class="bg-surface-container rounded-xl p-12 text-center" @if($planes->count() > 0) hidden @endif>
                <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4" aria-hidden="true">flight_land</span>
                <p class="text-on-surface-variant text-lg">No se han detectado aviones en la última hora.</p>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/airflight-map.js')

    {{-- Fechas en hora local del navegador, formato español.

         El backend manda `seen_last_at` en ISO-8601 UTC sin ambigüedad
         (`AirFlightController::seenLastAtAsUtcIso()`). Aquí se convierte a
         la zona horaria que detecte el navegador (`Intl.DateTimeFormat`);
         si no se puede detectar o no la admite el motor de fechas, cae a
         Europe/Madrid. `window.formatLocalEsDate` queda expuesta para que
         la use también el sondeo de más abajo al reconstruir filas nuevas.

         Independiente de la paginación: reformatea las fechas que ya trae
         el HTML del servidor estés en la página que estés, no solo en la 1
         (eso sí lo es el sondeo de refresco automático, ver más abajo). --}}
    <script>
        (function () {
            function getDetectedTimezone() {
                try {
                    return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Europe/Madrid';
                } catch (e) {
                    return 'Europe/Madrid';
                }
            }

            var TIMEZONE = getDetectedTimezone();

            window.formatLocalEsDate = function (isoUtc) {
                if (!isoUtc) {
                    return '-';
                }

                var date = new Date(isoUtc);

                if (isNaN(date.getTime())) {
                    return isoUtc;
                }

                var options = {
                    timeZone: TIMEZONE,
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                };

                try {
                    return new Intl.DateTimeFormat('es-ES', options).format(date);
                } catch (e) {
                    options.timeZone = 'Europe/Madrid';
                    return new Intl.DateTimeFormat('es-ES', options).format(date);
                }
            };

            var timezoneLabel = document.getElementById('detected-timezone');
            if (timezoneLabel) {
                timezoneLabel.textContent = TIMEZONE;
            }

            document.querySelectorAll('[data-seen-at]').forEach(function (cell) {
                cell.textContent = window.formatLocalEsDate(cell.dataset.seenAt);
            });
        })();
    </script>

    {{-- Sondeo de la tabla "Aviones detectados (última hora)".

         Independiente del mapa: no toca urlAircrafts/urlReceiver ni el
         sondeo de 5s del mapa en vivo (receiver().refresh). Arranca al
         terminar de cargar la página entera (evento `load`, no
         DOMContentLoaded) y sólo si se está viendo la página 1 de la
         paginación, para no pisarle a alguien que esté navegando el
         historial. La primera pintura es la que ya trae el HTML del
         servidor: no se hace ninguna petición extra hasta pasado el primer
         minuto. --}}
    <script>
        (function () {
            var section = document.getElementById('detected-planes-section');

            if (!section || section.dataset.page !== '1') {
                return;
            }

            var url = section.dataset.url;
            var tbody = document.getElementById('detected-planes-tbody');
            var tableWrap = document.getElementById('detected-planes-table-wrap');
            var emptyState = document.getElementById('detected-planes-empty');
            var pagination = document.getElementById('detected-planes-pagination');
            var status = document.getElementById('detected-planes-status');

            var CELL_CLASS = 'px-3 py-2 text-center text-on-surface';

            function cell(value, extraClass) {
                var td = document.createElement('td');
                td.className = extraClass ? CELL_CLASS + ' ' + extraClass : CELL_CLASS;
                td.textContent = (value === null || value === undefined || value === '') ? '-' : value;
                return td;
            }

            // Flecha (icono "navigation" de Material Symbols, apunta hacia
            // arriba por defecto) rotada a los grados de la ruta, igual que
            // en el HTML que pinta el servidor: la misma orientación
            // aproximada de un vistazo, no solo el número.
            function directionCell(track) {
                var td = document.createElement('td');
                td.className = CELL_CLASS;

                if (track === null || track === undefined) {
                    td.textContent = '-';
                    return td;
                }

                var icon = document.createElement('span');
                icon.className = 'material-symbols-outlined text-base align-middle';
                icon.setAttribute('aria-hidden', 'true');
                icon.style.transform = 'rotate(' + track + 'deg)';
                icon.textContent = 'navigation';

                td.appendChild(icon);
                td.appendChild(document.createTextNode(' ' + track + '°'));

                return td;
            }

            function buildRow(plane) {
                var tr = document.createElement('tr');
                tr.className = 'bg-surface-container-lowest border-b border-outline-variant/20';

                tr.appendChild(cell(plane.icao, 'font-mono'));
                tr.appendChild(cell(plane.squawk));
                tr.appendChild(cell(plane.flight, 'font-bold'));
                tr.appendChild(cell(plane.altitude));
                tr.appendChild(cell(plane.speed));
                tr.appendChild(directionCell(plane.track));
                tr.appendChild(cell(window.formatLocalEsDate(plane.seen_last_at)));

                return tr;
            }

            function applyPlanes(planes) {
                tbody.textContent = '';

                if (!planes.length) {
                    tableWrap.hidden = true;
                    emptyState.hidden = false;
                } else {
                    planes.forEach(function (plane) {
                        tbody.appendChild(buildRow(plane));
                    });
                    tableWrap.hidden = false;
                    emptyState.hidden = true;
                }

                // A partir del primer refresco esto ya no es "página 1 de N"
                // del servidor, es "lo último detectado": la paginación deja
                // de tener sentido.
                if (pagination) {
                    pagination.hidden = true;
                }

                if (status) {
                    status.textContent = 'Actualizado automáticamente cada minuto · última vez a las '
                        + new Date().toLocaleTimeString('es-ES');
                }
            }

            function fetchDetected() {
                fetch(url, { headers: { Accept: 'application/json' } })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }

                        return response.json();
                    })
                    .then(function (json) {
                        applyPlanes(Array.isArray(json.data) ? json.data : []);
                    })
                    .catch(function (error) {
                        if (status) {
                            status.textContent = 'Sin conexión al refrescar · reintentando…';
                        }
                        console.error('Error updating detected planes:', error);
                    });
            }

            window.addEventListener('load', function () {
                setInterval(fetchDetected, 60000);
            });
        })();
    </script>
@endpush
