@extends('layouts.app')

@section('title', $plant->name.' | Smart Plant | Api Raupulus')
{{--
    En plano y recortada: la descripción admite HTML básico desde que se pinta
    con `safeBasic()`, y una `<meta>` no. Si se cuela un `<p>` aquí, sale tal
    cual en el resultado de búsqueda.
--}}
@section('description', \App\Helpers\HtmlHelper::toMetaDescription($plant->description) ?: 'Perfil y estadísticas de '.$plant->name.' en Smart Plant')
@section('keywords', 'smart plant, bonsai, '.$plant->name.', '.$plant->name_scientific.', sensores, riego')

@section('rs-title', $plant->name.' - Smart Plant')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', \App\Helpers\HtmlHelper::toMetaDescription($plant->description) ?: 'Perfil y estadísticas de '.$plant->name)
@section('rs-image', $plant->url_image)
@section('rs-url', route('smartplant.show', $plant))

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[30vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tighter mb-2">#{{ $plant->id }} - {{ $plant->name }}</h1>
            @if($plant->name_scientific)
                <p class="text-lg text-white/80 italic">{{ $plant->name_scientific }}</p>
            @endif
            @if($plant->start_at)
                <div class="inline-flex items-center gap-2 bg-white/15 rounded-full px-4 py-1.5 text-sm text-white mt-4">
                    <span class="material-symbols-outlined text-base">eco</span>
                    Siembra: {{ $plant->start_at->format('d/m/Y') }}
                </div>
            @endif
        </div>
    </section>

    {{-- Volver --}}
    <section class="pt-8 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <a href="{{ route('smartplant.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container rounded-lg text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Volver a Smart Plant
            </a>
        </div>
    </section>

    {{-- Perfil de la planta --}}
    <section class="py-12 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 md:p-8 flex flex-col md:flex-row gap-8">
                <img src="{{ $plant->url_image }}"
                     alt="{{ $plant->name }}"
                     class="w-full md:w-64 h-64 object-cover rounded-lg shrink-0">

                <div class="flex-1">
                    {{--
                        La descripción se escribe desde la intranet y admite
                        HTML básico. `safeBasic()` deja pasar sólo un puñado de
                        etiquetas: `{!! !!}` a secas sobre texto que alguien
                        escribe es una puerta abierta.
                    --}}
                    <div class="text-on-surface-variant mb-6 prose-basica">
                        @if(filled($plant->description))
                            @safeHtml($plant->description)
                        @else
                            Sin descripción
                        @endif
                    </div>

                    {{--
                        Igual que la descripción: se escribe desde la intranet
                        con HTML básico ya incluido (`<p>`, `<strong>`, `<br>`
                        por sección), y `safeBasic()` es lo que lo deja pasar
                        sin abrir la puerta a un `<script>`. Antes se partía a
                        mano por líneas en blanco y se negrita la primera
                        palabra antes de «:», una convención de cuando el
                        campo era texto plano; con HTML de verdad ya en el
                        campo, ese partido a mano sólo conseguía escapar las
                        propias etiquetas y enseñarlas tal cual.
                    --}}
                    @if(filled($plant->details))
                        <div class="text-on-surface-variant prose-basica">
                            @safeHtml($plant->details)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Estadísticas rápidas del último registro --}}
    @if($registers->count() > 0)
        @php $lastRegister = $registers->first(); @endphp
        <section class="pb-12 bg-surface">
            <div class="max-w-5xl mx-auto px-6">
                <h3 class="text-2xl text-on-surface font-bold leading-none mb-4">Última lectura</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-3">
                    <div class="bg-surface-container-lowest rounded-lg p-3 text-center shadow-lg">
                        <span class="text-on-surface-variant text-xs">Humedad Tierra</span>
                        <p class="text-on-surface font-bold text-lg">{{ $lastRegister->soil_humidity ?? '-' }}%</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-lg p-3 text-center shadow-lg">
                        <span class="text-on-surface-variant text-xs">Temperatura</span>
                        <p class="text-on-surface font-bold text-lg">{{ $lastRegister->temperature ?? '-' }}°C</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-lg p-3 text-center shadow-lg">
                        <span class="text-on-surface-variant text-xs">Humedad Aire</span>
                        <p class="text-on-surface font-bold text-lg">{{ $lastRegister->humidity ?? '-' }}%</p>
                    </div>
                    <div class="bg-surface-container-lowest rounded-lg p-3 text-center shadow-lg">
                        <span class="text-on-surface-variant text-xs">Presión</span>
                        <p class="text-on-surface font-bold text-lg">{{ $lastRegister->pressure ?? '-' }} hPa</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div class="bg-surface-container-lowest rounded-lg p-3 text-center shadow-lg">
                        <span class="text-on-surface-variant text-xs">Radiación UV</span>
                        <p class="text-on-surface font-bold text-lg">{{ $lastRegister->uv ?? '-' }}</p>
                    </div>
                    {{--
                        Tokens del sistema, no `bg-green-100`/`text-green-700`:
                        aquéllos son colores fijos de Tailwind sin variante
                        oscura, así que en modo oscuro el fondo se quedaba claro
                        con la etiqueta encima también clara.
                    --}}
                    @foreach ([
                        ['on' => $lastRegister->full_water_tank, 'icon' => 'water_full', 'label' => 'Tanque lleno'],
                        ['on' => $lastRegister->waterpump_enabled, 'icon' => 'sprinkler', 'label' => 'Riego activo'],
                        ['on' => $lastRegister->vaporizer_enabled, 'icon' => 'humidity_high', 'label' => 'Vaporizador'],
                    ] as $estado)
                        <div class="rounded-lg p-3 text-center shadow-lg {{ $estado['on'] ? 'bg-success-container' : 'bg-surface-container-lowest' }}">
                            <span class="material-symbols-outlined block {{ $estado['on'] ? 'text-on-success-container' : 'text-on-surface-variant' }}">{{ $estado['icon'] }}</span>
                            <span class="text-xs {{ $estado['on'] ? 'text-on-success-container font-semibold' : 'text-on-surface-variant' }}">{{ $estado['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Mínimos/máximos por sensor: hoy, esta semana y este mes --}}
    @if(count($statCards) > 0)
        <section class="pb-12 bg-surface">
            <div class="max-w-7xl mx-auto px-6">
                <h3 class="text-2xl text-on-surface font-bold leading-none mb-4">Mínimos y máximos</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($statCards as $card)
                        <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6">
                            <h4 class="text-on-surface font-bold mb-3">{{ $card['label'] }}</h4>
                            <div class="grid grid-cols-3 gap-3 text-sm">
                                @foreach($card['stats'] as $stat)
                                    <div class="text-center">
                                        <span class="text-on-surface-variant text-xs">{{ $stat['label'] }}</span>
                                        <p class="text-on-surface font-bold">
                                            {{ $stat['min'] ?? '-' }}{{ $card['unit'] }} / {{ $stat['max'] ?? '-' }}{{ $card['unit'] }}
                                        </p>
                                        <span class="text-on-surface-variant text-xs">mín / máx</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Tabla de últimas 50 lecturas --}}
    <section class="pb-16 bg-surface">
        <div class="max-w-7xl mx-auto px-6">
            <h3 class="text-2xl text-on-surface font-bold leading-none mb-4">Últimas {{ $registers->count() }} lecturas</h3>

            <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6">
                @if($registers->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-max w-full table-auto text-sm">
                            <thead>
                                <tr class="bg-inverse-surface text-inverse-on-surface">
                                    <td class="px-4 py-2 text-center">Fecha</td>
                                    <td class="px-4 py-2 text-center">Hum. Tierra</td>
                                    <td class="px-4 py-2 text-center">Temp.</td>
                                    <td class="px-4 py-2 text-center">Hum. Aire</td>
                                    <td class="px-4 py-2 text-center">Presión</td>
                                    <td class="px-4 py-2 text-center">UV</td>
                                    <td class="px-4 py-2 text-center">Tanque lleno</td>
                                    <td class="px-4 py-2 text-center">Riego activo</td>
                                    <td class="px-4 py-2 text-center">Vaporizador</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($registers as $register)
                                    <tr class="bg-surface-container-lowest border-b border-outline-variant/20">
                                        <td class="px-4 py-2 text-center text-on-surface-variant">{{ $register->created_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                        <td class="px-4 py-2 text-center text-on-surface font-semibold">{{ $register->soil_humidity ?? '-' }}%</td>
                                        <td class="px-4 py-2 text-center text-on-surface font-semibold">{{ $register->temperature ?? '-' }}°C</td>
                                        <td class="px-4 py-2 text-center text-on-surface font-semibold">{{ $register->humidity ?? '-' }}%</td>
                                        <td class="px-4 py-2 text-center text-on-surface font-semibold">{{ $register->pressure ?? '-' }}</td>
                                        <td class="px-4 py-2 text-center text-on-surface font-semibold">{{ $register->uv ?? '-' }}</td>
                                        <td class="px-4 py-2 text-center font-semibold {{ $register->full_water_tank ? 'text-on-success-container' : 'text-on-surface-variant' }}">{{ $register->full_water_tank ? 'Sí' : 'No' }}</td>
                                        <td class="px-4 py-2 text-center font-semibold {{ $register->waterpump_enabled ? 'text-on-success-container' : 'text-on-surface-variant' }}">{{ $register->waterpump_enabled ? 'Sí' : 'No' }}</td>
                                        <td class="px-4 py-2 text-center font-semibold {{ $register->vaporizer_enabled ? 'text-on-success-container' : 'text-on-surface-variant' }}">{{ $register->vaporizer_enabled ? 'Sí' : 'No' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">potted_plant</span>
                        <p class="text-on-surface-variant text-lg">No hay lecturas registradas todavía para esta planta.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
