@extends('layouts.app')

@section('title', 'Smart Plant | Api Raupulus')
@section('description', 'Monitorización inteligente de plantas y bonsais con sensores de humedad, temperatura y luz')
@section('keywords', 'smart plant, bonsai, plantas, sensores, Raúl Caro Pastorino, raupulus, esp32, riego')

@section('rs-title', 'Smart Plant - Monitorización de plantas')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Monitorización inteligente de plantas y bonsais')
@section('rs-image', asset('images/smart-plant/social-thumbnail.jpg'))
@section('rs-url', route('smartplant.index'))

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[40vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Smart Plant</h1>
            <p class="text-xl text-white/80">Monitorización inteligente de plantas y bonsais</p>
        </div>
    </section>

    {{-- Descripción --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <h3 class="text-3xl text-on-surface font-bold leading-none mb-3">Sobre este módulo</h3>
            <p class="text-on-surface-variant mb-3">
                Proyecto para monitorizar plantas delicadas, principalmente bonsais pudiendo observar el comportamiento asegurando cubrir necesidades manteniendo así la salud o descubrir problemas.
            </p>
            <p class="text-on-surface-variant mb-3">
                Utilizo un <strong>esp32 lite</strong> conectado a una placa solar y sensores para medir la humedad en tierra/aire, temperatura, luz y tanque de agua está lleno antes de activar el riego.
            </p>
            <p class="text-on-surface-variant mb-3">
                Código del proyecto en <strong>C++</strong>:
                <a href="https://gitlab.com/raupulus/esp32-smart-bonsai" class="underline text-on-tertiary-container font-bold text-xs" target="_blank">
                    https://gitlab.com/raupulus/esp32-smart-bonsai
                </a>
            </p>
        </div>
    </section>

    {{-- Listado de plantas --}}
    <section class="py-12 bg-surface-container-low">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-on-surface mb-6">Plantas registradas</h2>

            @if($smartplants->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($smartplants as $plant)
                        <div class="bg-surface-container-lowest rounded-xl p-6 shadow-lg">
                            <div class="w-12 h-12 bg-surface-container rounded-lg flex items-center justify-center mb-4">
                                <span class="material-symbols-outlined text-green-700">potted_plant</span>
                            </div>
                            <h3 class="text-xl font-bold text-on-surface mb-2">{{ $plant->name ?? 'Planta #'.$plant->id }}</h3>
                            <p class="text-on-surface-variant text-sm mb-2">{{ $plant->description ?? 'Sin descripción' }}</p>
                            @if($plant->registers && $plant->registers->count() > 0)
                                @php $lastRegister = $plant->registers->first(); @endphp
                                <div class="grid grid-cols-2 gap-2 mt-4 text-sm">
                                    <div class="bg-surface-container rounded-lg p-2 text-center">
                                        <span class="text-on-surface-variant text-xs">Humedad Tierra</span>
                                        <p class="text-on-surface font-bold">{{ $lastRegister->soil_humidity ?? '-' }}%</p>
                                    </div>
                                    <div class="bg-surface-container rounded-lg p-2 text-center">
                                        <span class="text-on-surface-variant text-xs">Temperatura</span>
                                        <p class="text-on-surface font-bold">{{ $lastRegister->temperature ?? '-' }}°C</p>
                                    </div>
                                    <div class="bg-surface-container rounded-lg p-2 text-center">
                                        <span class="text-on-surface-variant text-xs">Humedad Aire</span>
                                        <p class="text-on-surface font-bold">{{ $lastRegister->humidity ?? '-' }}%</p>
                                    </div>
                                    <div class="bg-surface-container rounded-lg p-2 text-center">
                                        <span class="text-on-surface-variant text-xs">Luz</span>
                                        <p class="text-on-surface font-bold">{{ $lastRegister->light ?? '-' }}%</p>
                                    </div>
                                </div>

                                {{-- Tabla de últimas 10 lecturas --}}
                                @if($plant->registers->count() > 1)
                                    <details class="mt-4">
                                        <summary class="text-xs text-on-tertiary-container font-bold cursor-pointer hover:underline">
                                            Ver últimas {{ $plant->registers->count() }} lecturas
                                        </summary>
                                        <div class="overflow-x-auto mt-2">
                                            <table class="w-full text-xs text-on-surface">
                                                <thead class="bg-surface-container text-on-surface-variant uppercase">
                                                    <tr>
                                                        <th class="px-2 py-1">Fecha</th>
                                                        <th class="px-2 py-1">Hum. Tierra</th>
                                                        <th class="px-2 py-1">Temp.</th>
                                                        <th class="px-2 py-1">Hum. Aire</th>
                                                        <th class="px-2 py-1">Luz</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($plant->registers as $register)
                                                        <tr class="border-b border-surface-container">
                                                            <td class="px-2 py-1">{{ $register->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                                            <td class="px-2 py-1 text-center">{{ $register->soil_humidity ?? '-' }}%</td>
                                                            <td class="px-2 py-1 text-center">{{ $register->temperature ?? '-' }}°C</td>
                                                            <td class="px-2 py-1 text-center">{{ $register->humidity ?? '-' }}%</td>
                                                            <td class="px-2 py-1 text-center">{{ $register->light ?? '-' }}%</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-surface-container rounded-xl p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">potted_plant</span>
                    <p class="text-on-surface-variant text-lg">No hay plantas registradas actualmente.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
