@extends('layouts.app')

@section('title', $cv->title.' | Currículum | Api Raupulus')
@section('description', \App\Helpers\HtmlHelper::toMetaDescription($cv->presentation) ?: 'Currículum de Raúl Caro Pastorino: '.$cv->title)
@section('keywords', 'curriculum, cv, raupulus, Raúl Caro Pastorino, '.$cv->title.', desarrollador, programador')

@section('rs-title', $cv->title.' - Currículum')
@section('rs-sitename', 'Api Raupulus')
@section('rs-description', \App\Helpers\HtmlHelper::toMetaDescription($cv->presentation) ?: 'Currículum de Raúl Caro Pastorino')
@section('rs-image', $cv->url_image)
@section('rs-url', route('cv.show', ['slug' => $cv->slug]))

@section('content')
    {{-- Volver --}}
    <section class="pt-24 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <a href="{{ route('cv.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container rounded-lg text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Volver a Currículum
            </a>
        </div>
    </section>

    {{-- Cabecera --}}
    <section class="pt-8 pb-4 bg-surface">
        <div class="max-w-5xl mx-auto px-6">
            <div class="bg-surface-container-lowest rounded-xl shadow-lg p-6 md:p-8 flex flex-col sm:flex-row sm:items-start gap-6">
                <img src="{{ $cv->url_image }}"
                     alt="{{ $cv->title }}"
                     class="w-24 h-24 rounded-full object-cover shrink-0">

                <div class="flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold text-on-surface">{{ $cv->title }}</h1>
                            @if($cv->user)
                                <p class="text-on-surface-variant text-sm mt-1">{{ $cv->user->full_name }}</p>
                            @endif
                        </div>

                        {{-- Botón de descarga, esquina superior derecha. Solo si el CV admite descarga. --}}
                        @if($cv->is_downloadable)
                            <x-button :href="route('cv.pdf', ['slug' => $cv->slug])" icon="download" class="shrink-0">
                                Descargar PDF
                            </x-button>
                        @endif
                    </div>

                    @if(filled($cv->presentation))
                        <p class="text-on-surface-variant mt-4 whitespace-pre-line">{{ $cv->presentation }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{--
        Cada sección se pinta igual: título y, por cada fila, lo que tenga
        (mismo criterio que `cv/pdf.blade.php`, adaptado a Tailwind y con las
        fechas ya convertidas —las columnas `start_at`/`end_at` no llevan cast
        a Carbon en el modelo, así que aquí se parsean a mano).
    --}}
    @php
        $timeline = [
            'Experiencia acreditada' => $cv->experienceAccredited,
            'Experiencia no acreditada' => $cv->experienceNoAccredited,
            'Autónomo' => $cv->experienceSelfEmployed,
            'Otra experiencia' => $cv->experienceOther,
            'Formación académica' => $cv->academicTraining,
            'Formación complementaria' => $cv->academicComplementary,
            'Formación online' => $cv->academicComplementaryOnline,
        ];

        $listed = [
            'Proyectos' => ['icon' => 'rocket_launch', 'rows' => $cv->projects],
            'Repositorios' => ['icon' => 'code', 'rows' => $cv->repositories],
            'Servicios' => ['icon' => 'handyman', 'rows' => $cv->services],
            'Colaboraciones' => ['icon' => 'handshake', 'rows' => $cv->collaborations],
            'Trabajos' => ['icon' => 'work', 'rows' => $cv->jobs],
            'Aficiones' => ['icon' => 'interests', 'rows' => $cv->hobbies],
        ];
    @endphp

    {{-- Habilidades --}}
    @if($cv->skills->isNotEmpty())
        <section class="py-8 bg-surface">
            <div class="max-w-5xl mx-auto px-6">
                <h2 class="text-2xl font-bold text-on-surface mb-4">Habilidades</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($cv->skills as $skill)
                        <div class="bg-surface-container-lowest rounded-lg p-4 shadow">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-on-surface">{{ $skill->name }}</span>
                                @if($skill->level)
                                    <span class="text-xs text-on-surface-variant">{{ $skill->level }}/10</span>
                                @endif
                            </div>
                            @if($skill->level)
                                <div class="w-full bg-surface-container rounded-full h-2">
                                    <div class="bg-primary-container h-2 rounded-full" style="width: {{ min(100, max(0, $skill->level * 10)) }}%"></div>
                                </div>
                            @endif
                            @if(filled($skill->description))
                                <p class="text-on-surface-variant text-sm mt-2">{{ $skill->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Experiencia y formación, en línea de tiempo --}}
    @foreach($timeline as $title => $rows)
        @continue($rows->isEmpty())
        <section class="py-8 bg-surface odd:bg-surface-container-low">
            <div class="max-w-5xl mx-auto px-6">
                <h2 class="text-2xl font-bold text-on-surface mb-4">{{ $title }}</h2>
                <div class="flex flex-col gap-4">
                    @foreach($rows as $row)
                        <div class="bg-surface-container-lowest rounded-lg p-5 shadow">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <h3 class="font-bold text-on-surface">{{ $row->title }}</h3>
                                @php
                                    $start = $row->start_at ? \Illuminate\Support\Carbon::parse($row->start_at) : null;
                                    $end = $row->end_at ? \Illuminate\Support\Carbon::parse($row->end_at) : null;
                                @endphp
                                @if($start)
                                    <span class="text-xs text-on-surface-variant shrink-0">
                                        {{ $start->format('m/Y') }} – {{ $end ? $end->format('m/Y') : 'actualidad' }}
                                    </span>
                                @endif
                            </div>
                            @if(filled($row->position) || filled($row->company) || filled($row->entity))
                                <p class="text-sm text-on-tertiary-container font-semibold mt-1">
                                    {{ collect([$row->position ?? null, $row->company ?? $row->entity ?? null])->filter()->implode(' · ') }}
                                </p>
                            @endif
                            @if(filled($row->description))
                                <p class="text-on-surface-variant text-sm mt-2">{{ $row->description }}</p>
                            @endif
                            @if(filled($row->learned))
                                <p class="text-on-surface-variant text-sm mt-2"><strong>Aprendido:</strong> {{ $row->learned }}</p>
                            @endif
                            @if(filled($row->note))
                                <p class="text-on-surface-variant text-xs mt-2 italic">{{ $row->note }}</p>
                            @endif
                            @if(filled($row->credential_url))
                                <a href="{{ $row->credential_url }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 text-xs text-on-tertiary-container font-bold uppercase tracking-widest mt-3 hover:underline">
                                    Ver credencial
                                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    {{-- Proyectos, repositorios, servicios, colaboraciones, trabajos, aficiones --}}
    @foreach($listed as $title => $group)
        @continue($group['rows']->isEmpty())
        <section class="py-8 bg-surface odd:bg-surface-container-low">
            <div class="max-w-5xl mx-auto px-6">
                <h2 class="text-2xl font-bold text-on-surface mb-4">{{ $title }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($group['rows'] as $row)
                        @php $link = $row->url ?? null; @endphp
                        <div class="bg-surface-container-lowest rounded-lg p-5 shadow">
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-on-tertiary-container shrink-0">{{ $group['icon'] }}</span>
                                <div class="flex-1">
                                    <h3 class="font-bold text-on-surface">{{ $row->title ?? $row->name }}</h3>
                                    @if(filled($row->role))
                                        <p class="text-xs text-on-surface-variant">{{ $row->role }}</p>
                                    @endif
                                    @if(filled($row->description))
                                        <p class="text-on-surface-variant text-sm mt-2">{{ $row->description }}</p>
                                    @endif
                                    <div class="flex flex-wrap gap-3 mt-2">
                                        @if($link)
                                            <a href="{{ $link }}" target="_blank" rel="noopener" class="text-xs text-on-tertiary-container font-bold hover:underline">Sitio</a>
                                        @endif
                                        @if(filled($row->urlinfo ?? null))
                                            <a href="{{ $row->urlinfo }}" target="_blank" rel="noopener" class="text-xs text-on-tertiary-container font-bold hover:underline">Info</a>
                                        @endif
                                        @if(filled($row->repository ?? null))
                                            <a href="{{ $row->repository }}" target="_blank" rel="noopener" class="text-xs text-on-tertiary-container font-bold hover:underline">Repositorio</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach
@endsection
