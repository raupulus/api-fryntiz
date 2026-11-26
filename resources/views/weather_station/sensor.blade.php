@extends('layouts.app')

@section('title', $title . ' en Chipiona | Weather Station | Api Raupulus')
@section('description', 'Histórico completo y valores extremos del sensor de ' . mb_strtolower($title) . ' registrados por la estación meteorológica de Chipiona.')
@section('canonical', route('weather_station.sensor', array_filter(['type' => $type, 'station' => $stationId ?? null])))

@push('json-ld')
    @php
        $sensorSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Portada',
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Estación Meteorológica',
                    'item' => route('weather_station.index'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $title,
                    'item' => route('weather_station.sensor', array_filter(['type' => $type, 'station' => $stationId ?? null])),
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">@json($sensorSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)</script>
@endpush

@section('content')
    {{-- Hero --}}
    <x-page-hero image="images/heroes/weather-station.webp" min-height="min-h-[30svh]">
        <div class="flex items-center gap-4 mb-4">
            <span class="material-symbols-outlined text-4xl" aria-hidden="true">{{ $icon }}</span>
            <h1 class="text-4xl md:text-5xl font-bold tracking-tighter">{{ $title }}</h1>
        </div>
        <p class="text-lg text-on-hero/90">
            @if(!empty($stationName))
                Datos del sensor — {{ $stationName }}
            @else
                Datos del sensor — Estación meteorológica Chipiona
            @endif
        </p>
    </x-page-hero>

    {{-- Botón volver + Tabla --}}
    <section class="py-12 bg-surface">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
                <a href="{{ route('weather_station.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container rounded-lg text-on-surface hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">arrow_back</span>
                    Volver a Weather Station
                </a>

                <a href="{{ $nextUrl }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container rounded-lg text-on-surface hover:bg-surface-container-high transition-colors">
                    Siguiente: {{ $nextTitle }}
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">arrow_forward</span>
                </a>
            </div>

            <div class="bg-surface-container-lowest rounded-xl shadow-lg p-4 sm:p-6">
                @if($records->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-max w-full table-auto text-sm">
                            <caption class="sr-only">Lecturas históricas de {{ $title }}</caption>
                            <thead>
                                <tr class="bg-inverse-surface text-inverse-on-surface">
                                    @foreach($columns as $label)
                                        <th scope="col" class="px-4 py-2 text-center font-semibold">{{ $label }}</th>
                                    @endforeach
                                    <th scope="col" class="px-4 py-2 text-center font-semibold">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($records as $record)
                                    @php
                                        $localCreatedAt = $record->created_at?->timezone(config('app.display_timezone', 'Europe/Madrid'));
                                    @endphp
                                    <tr class="bg-surface-container-lowest border-b border-outline-variant/20">
                                        @foreach($columns as $field => $label)
                                            @php
                                                $value = $record->{$field};
                                                $cellType = $cellsInfo[$field]['type'] ?? 'string';
                                            @endphp
                                            <td class="px-4 py-2 text-center text-on-surface font-semibold">
                                                @if($cellType === 'bool')
                                                    {{ $value ? 'Sí' : 'No' }}
                                                @elseif($cellType === 'float' && is_numeric($value))
                                                    {{ round($value, 2) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-2 text-center text-on-surface-variant">
                                            @if($localCreatedAt)
                                                <time datetime="{{ $localCreatedAt->toIso8601String() }}">{{ $localCreatedAt->translatedFormat('d/m/Y H:i:s') }}</time>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $records->links() }}
                    </div>
                @elseif($records->total() > 0)
                    <div class="text-center py-12">
                        <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4" aria-hidden="true">{{ $icon }}</span>
                        <p class="text-on-surface-variant text-lg mb-4">La página solicitada supera los registros disponibles para este sensor.</p>
                        <x-button :href="$records->url(1)">Volver a la primera página</x-button>
                    </div>
                @else
                    <div class="text-center py-12">
                        <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4" aria-hidden="true">{{ $icon }}</span>
                        <p class="text-on-surface-variant text-lg">No hay datos disponibles para este sensor.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
