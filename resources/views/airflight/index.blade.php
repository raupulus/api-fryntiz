@extends('layouts.app')

@section('title', 'Vuelos en tiempo real sobre Chipiona')
@section('description', 'Registro de vuelos en tiempo real para aviones en Chipiona y alrededores')
@section('keywords', 'vuelos, aviones, chipiona, Raúl Caro Pastorino, raupulus, airplanes, airflight')

@section('rs-title', 'Vuelos en tiempo real sobre Chipiona')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Registro de vuelos en tiempo real para aviones en Chipiona y alrededores')
@section('rs-image', asset('images/airflight/social-thumbnail.jpg'))
@section('rs-url', route('airflight.index'))
@section('rs-image-alt', 'Vuelos en tiempo real sobre Chipiona')

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Airflight</h1>
            <p class="text-xl text-white/80">Registros de vuelos en tiempo real — Chipiona</p>
        </div>
    </section>

    {{-- Descripción --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <div class="bg-error-container border border-error/30 mb-5 text-on-surface px-4 py-3 rounded-lg" role="alert">
                <span class="text-sm">
                    Sitio <strong>temporal</strong> con la finalidad de comprobar el funcionamiento de los dispositivos que uso para recopilar los datos (raspberry pi y capturadora de televisión digital con una antena modificada para 1090Mhz)
                </span>
            </div>

            <h3 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre esta api</h3>
            <p class="text-on-surface-variant mb-2">
                Básicamente monitorizo la señal que emiten los aviones enviando su posición para no chocarse entre ellos obteniendo esos datos por ondas que decodifico y parseo para poder sacar estadísticas y trazar rutas de vuelos en tiempo real.
            </p>
            <p class="text-on-surface-variant mb-2">
                Los datos pueden no ser precisos debido a que aún se encuentra en depuración mientras voy detectando posibles problemas en el hardware que he manipulado y el software que le he construido.
            </p>
            <p class="text-on-surface-variant mb-2">
                Puedes ver el desarrollo del programa:
                <a href="https://gitlab.com/raupulus/dump1090-to-db" class="underline text-on-tertiary-container font-bold text-xs" target="_blank">
                    https://gitlab.com/raupulus/dump1090-to-db
                </a>
            </p>
            <div class="bg-surface-container border border-outline-variant/30 my-4 px-4 py-2 rounded-lg text-center">
                <span class="font-bold text-on-surface">Franja horaria UTC +0:00</span>
            </div>
        </div>
    </section>

    {{-- Tabla de aviones detectados --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6">Aviones detectados (última hora)</h2>

            @if($planes->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-max w-full table-auto text-sm">
                        <thead>
                            <tr class="bg-inverse-surface text-inverse-on-surface">
                                <td class="px-3 py-2 text-center">ICAO</td>
                                <td class="px-3 py-2 text-center">Callsign</td>
                                <td class="px-3 py-2 text-center">Altitud (ft)</td>
                                <td class="px-3 py-2 text-center">Velocidad (kt)</td>
                                <td class="px-3 py-2 text-center">Dirección</td>
                                <td class="px-3 py-2 text-center">Latitud</td>
                                <td class="px-3 py-2 text-center">Longitud</td>
                                <td class="px-3 py-2 text-center">Squawk</td>
                                <td class="px-3 py-2 text-center">Visto última vez</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($planes as $plane)
                                <tr class="bg-surface-container-lowest border-b border-outline-variant/20">
                                    <td class="px-3 py-2 text-center text-on-surface font-mono">{{ $plane->icao ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface font-bold">{{ $plane->callsign ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->altitude ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->speed ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->track ?? '-' }}°</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->lat ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->lon ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->squawk ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center text-on-surface">{{ $plane->seen_last_at ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $planes->links() }}
                </div>
            @else
                <div class="bg-surface-container rounded-xl p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">flight_land</span>
                    <p class="text-on-surface-variant text-lg">No se han detectado aviones en la última hora.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
