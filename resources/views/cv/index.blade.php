@extends('layouts.app')

@section('title', 'Currículums de Raúl Caro | Api Raupulus')
@section('description', 'Currículums públicos de Raúl Caro Pastorino (@raupulus) adaptados por perfil técnico con experiencia, proyectos y descarga en PDF.')
@section('keywords', 'curriculum, cv, raupulus, Raúl Caro Pastorino, desarrollador, programador, backend, frontend')

@section('rs-title', 'Currículums de Raúl Caro | Api Raupulus')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', 'Currículums públicos de Raúl Caro Pastorino con descarga directa en PDF')
@section('rs-url', route('cv.index'))

@section('content')
    {{-- Hero --}}
    <x-page-hero image="images/heroes/cv.webp" min-height="min-h-[30svh]">
        <h1 class="text-4xl md:text-6xl font-bold tracking-tighter mb-4">Currículum</h1>
        <p class="text-xl text-on-hero/90">Un perfil distinto según lo que busques: frontend, backend, o un resumen rápido.</p>
    </x-page-hero>

    <section class="py-12 bg-surface-container-low">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            @if($curricula->isEmpty())
                <div class="bg-surface-container rounded-xl p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4" aria-hidden="true">badge</span>
                    <p class="text-on-surface-variant text-lg">Todavía no hay currículums públicos disponibles.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
                    @foreach($curricula as $curriculum)
                        <a href="{{ route('cv.show', ['slug' => $curriculum->slug]) }}"
                           class="bg-surface-container-lowest rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow flex flex-col sm:flex-row sm:items-center gap-5 group">
                            <img src="{{ $curriculum->url_image }}"
                                 alt="{{ $curriculum->title }}"
                                 class="w-14 h-14 shrink-0 rounded-full object-cover">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="block text-lg font-bold text-on-surface group-hover:underline underline-offset-2 break-words">{{ $curriculum->title }}</span>
                                    @if($curriculum->is_default)
                                        <span class="text-xs font-bold text-on-tertiary-fixed bg-tertiary-fixed rounded-full px-3 py-1">Predeterminado</span>
                                    @endif
                                </div>
                                @if(filled($curriculum->presentation))
                                    <p class="text-on-surface-variant text-sm mt-1 line-clamp-2 break-words">{{ $curriculum->presentation }}</p>
                                @endif
                            </div>
                            <span class="material-symbols-outlined text-on-surface-variant shrink-0 self-start sm:self-center" aria-hidden="true">arrow_forward</span>
                        </a>
                    @endforeach
                </div>

                @if($curricula->hasPages())
                    <div class="mt-8">
                        {{ $curricula->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>
@endsection
