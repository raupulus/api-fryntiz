@extends('layouts.app')

@section('title', $title . ' | Weather Station | Api Raupulus')
@section('description', 'Datos del sensor de ' . $title . ' de la estación meteorológica de Chipiona')

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient min-h-[30vh] flex items-center pt-20">
        <div class="max-w-7xl mx-auto px-6 text-white">
            <div class="flex items-center gap-4 mb-4">
                <span class="material-symbols-outlined text-4xl">{{ $icon }}</span>
                <h1 class="text-4xl md:text-5xl font-bold tracking-tighter">{{ $title }}</h1>
            </div>
            <p class="text-lg text-white/80">
                @if(!empty($stationName))
                    Datos del sensor — {{ $stationName }}
                @else
                    Datos del sensor — Estación meteorológica Chipiona
                @endif
            </p>
        </div>
    </section>

    {{-- Botón volver + Tabla --}}
    <section class="py-12 bg-surface">
        <div class="max-w-7xl mx-auto px-6">
            <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
                <a href="{{ route('weather_station.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container rounded-lg text-on-surface hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Volver a Weather Station
                </a>

                <a href="{{ $nextUrl }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container rounded-lg text-on-surface hover:bg-surface-container-high transition-colors">
                    Siguiente: {{ $nextTitle }}
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6">
                @if($records->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-max w-full table-auto text-sm">
                            <thead>
                                <tr class="bg-inverse-surface text-inverse-on-surface">
                                    @foreach($columns as $label)
                                        <td class="px-4 py-2 text-center">{{ $label }}</td>
                                    @endforeach
                                    <td class="px-4 py-2 text-center">Fecha</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($records as $record)
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
                                            {{ $record->created_at ? $record->created_at->format('d/m/Y H:i:s') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $records->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">{{ $icon }}</span>
                        <p class="text-on-surface-variant text-lg">No hay datos disponibles para este sensor.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
