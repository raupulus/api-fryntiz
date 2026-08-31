<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $cv->title }}</title>
    <style>
        /* dompdf no entiende CSS moderno: nada de flex, grid ni variables. */
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; line-height: 1.45; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #ccc; text-transform: uppercase; letter-spacing: .5px; }
        h3 { font-size: 11px; margin: 0 0 2px; }
        p { margin: 0 0 6px; }
        .cabecera { margin-bottom: 14px; }
        .sutil { color: #666; font-size: 10px; }
        .bloque { margin-bottom: 10px; }
        ul { margin: 0; padding-left: 16px; }
    </style>
</head>
<body>

<div class="cabecera">
    <h1>{{ $cv->title }}</h1>
    @if ($cv->user)
        <p class="sutil">{{ $cv->user->full_name }} · {{ $cv->user->email }}</p>
    @endif
    @if ($cv->presentation)
        <p>{{ $cv->presentation }}</p>
    @endif
</div>

@php
    /*
     * Cada sección se pinta igual: título, y por cada fila lo que tenga. Se
     * recorren en el orden de `position`, que es el que se arrastra a mano en el
     * panel (B4).
     */
    $sections = [
        'Experiencia acreditada' => $cv->experienceAccredited,
        'Experiencia no acreditada' => $cv->experienceNoAccredited,
        'Autónomo' => $cv->experienceSelfEmployed,
        'Otra experiencia' => $cv->experienceOther,
        'Formación académica' => $cv->academicTraining,
        'Formación complementaria' => $cv->academicComplementary,
        'Formación online' => $cv->academicComplementaryOnline,
        'Habilidades' => $cv->skills,
        'Proyectos' => $cv->projects,
        'Repositorios' => $cv->repositories,
        'Servicios' => $cv->services,
        'Colaboraciones' => $cv->collaborations,
        'Trabajos' => $cv->jobs,
        'Aficiones' => $cv->hobbies,
    ];
@endphp

@foreach ($sections as $title => $rows)
    @continue(blank($rows) || $rows->isEmpty())

    <h2>{{ $title }}</h2>

    @foreach ($rows as $row)
        <div class="bloque">
            <h3>{{ $row->title ?? $row->name ?? '' }}</h3>

            @if (! empty($row->company) || ! empty($row->role))
                <p class="sutil">{{ collect([$row->role ?? null, $row->company ?? null])->filter()->implode(' · ') }}</p>
            @endif

            @if (! empty($row->start_at) || ! empty($row->end_at))
                <p class="sutil">
                    {{ optional($row->start_at)->format('m/Y') }}
                    @if (! empty($row->end_at)) – {{ optional($row->end_at)->format('m/Y') }} @else – actualidad @endif
                </p>
            @endif

            @if (! empty($row->description))
                <p>{{ $row->description }}</p>
            @endif

            @if (! empty($row->url))
                <p class="sutil">{{ $row->url }}</p>
            @endif
        </div>
    @endforeach
@endforeach

</body>
</html>
