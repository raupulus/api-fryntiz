@php
    /*
     * PDF del currículum con la maquetación del CV de 2024: columna principal a
     * la izquierda y barra lateral azul a la derecha, sólo en la primera página.
     *
     * DomPDF no entiende CSS moderno (nada de flex, grid ni variables): las
     * filas «título | fecha» son tablas y la barra lateral es un bloque con
     * posición absoluta. Qué va en la barra y qué en la columna principal lo
     * decide CurriculumDocument, para que nunca se corte nada.
     */
    $doc = \App\Services\Cv\CurriculumDocument::for($cv);
    $contact = $doc->contact();
    $qr = $doc->qrCodeDataUri();

    $font = fn (string $file): string => resource_path('fonts/lato/'.$file);
    $display = fn (?string $url): string => rtrim((string) preg_replace('#^https?://(www\.)?#', '', (string) $url), '/');
    $icon = fn (string $name, string $color = '#424242'): string => \App\Services\Cv\CurriculumDocument::icon($name, $color);

    $experience = $doc->experience();
    $otherExperience = $doc->otherExperience();
    $education = $doc->education();
    $complementary = $doc->complementary();
    $certifications = $doc->certifications();
    $skills = $doc->skills();
    $projects = $doc->projects();
    $jobs = $doc->jobs();
    $services = $doc->services();
    $collaborations = $doc->collaborations();
    $hobbies = $doc->hobbies();
    $repositories = $doc->repositories();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $doc->name() }} — {{ $doc->headline() }}</title>
    <style>
        @font-face { font-family: 'Lato'; font-style: normal; font-weight: normal; src: url('{{ $font('Lato-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Lato'; font-style: normal; font-weight: bold; src: url('{{ $font('Lato-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Lato'; font-style: italic; font-weight: normal; src: url('{{ $font('Lato-Italic.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Lato Light'; font-style: normal; font-weight: normal; src: url('{{ $font('Lato-Light.ttf') }}') format('truetype'); }

        @page { margin: 12mm 0 12mm 0; }
        body { margin: 0; font-family: 'Lato', 'DejaVu Sans', sans-serif; font-size: 9.5pt; line-height: 1.3; color: #424242; }
        a { color: #40638e; text-decoration: none; }
        p { margin: 0; }
        table { border-collapse: collapse; }

        /* Barra lateral: sólo en la primera página, de arriba abajo. */
        .sidebar { position: absolute; top: -2mm; right: 0; width: 70mm; height: 275mm; background: #40638e; color: #ffffff; }
        .sidebar-inner { padding: 5mm 5.5mm 0 5.5mm; }
        .sidebar a { color: #ffffff; }
        .logo { text-align: center; margin-bottom: 2mm; }
        .logo img { width: 25mm; height: 25mm; }
        .side-title { font-family: 'Lato Light'; font-size: 13pt; text-transform: uppercase; border-bottom: 0.6pt solid #ffffff; padding-bottom: 0.6mm; margin: 4mm 0 2mm; }
        .side-text { font-size: 8.5pt; line-height: 1.35; margin: 0 0 1.5mm; }
        .side-label { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; margin: 1.8mm 0 0.3mm; }
        .side-list { font-size: 8.5pt; line-height: 1.35; margin: 0 0 0.8mm; }
        .side-bottom { position: absolute; left: 0; right: 0; bottom: 0; }
        .qr-box { background: #464646; text-align: center; padding: 3.5mm 0 2.5mm; }
        .qr-box .qr { width: 23mm; height: 23mm; background: #ffffff; padding: 1mm; }
        .qr-label { color: #ffffff; font-size: 11pt; margin-top: 1.2mm; }
        .qr-label img { width: 3.6mm; height: 3.6mm; vertical-align: middle; }
        .buttons { width: 100%; }
        .buttons td { width: 50%; text-align: center; padding: 2.2mm 0; font-size: 11pt; }
        .buttons a { color: #ffffff; }
        .btn-linkedin { background: #0a66c2; }
        .btn-github { background: #111111; }
        .in-mark { display: inline-block; background: #ffffff; color: #0a66c2; font-weight: bold; font-size: 8pt; padding: 0 0.9mm; line-height: 1.25; }
        .gh-mark { width: 4mm; height: 4mm; vertical-align: middle; }

        /* Columna principal. */
        .main { margin: 0 73mm 0 11mm; }
        .name { font-family: 'Lato Light'; font-size: 29pt; line-height: 1.05; text-transform: uppercase; color: #424242; }
        .headline { font-size: 11pt; color: #40638e; margin: 1mm 0 3.5mm; }
        .contact { width: 100%; margin-bottom: 1mm; }
        .contact td { font-size: 9pt; padding: 0.7mm 1.5mm 0.7mm 0; vertical-align: top; }
        .contact img { width: 3.3mm; height: 3.3mm; vertical-align: middle; }
        .contact a { text-decoration: underline; }

        .section-title { font-family: 'Lato Light'; font-size: 17pt; text-transform: uppercase; color: #424242; border-bottom: 0.6pt solid #8a8a8a; padding-bottom: 0.4mm; margin: 5mm 0 2.6mm; }
        .section-title { page-break-after: avoid; }
        .entry-head, .entry-sub { page-break-after: avoid; }
        .entry { padding-left: 2mm; margin-bottom: 3.8mm; }
        .entry.keep { page-break-inside: avoid; }
        .profile p { margin: 0 0 1.8mm; }
        .entry-head { width: 100%; }
        .entry-head td { vertical-align: bottom; padding: 0; }
        .entry-title { font-size: 12.5pt; color: #424242; line-height: 1.2; }
        .entry-title.is-compact { font-size: 10.5pt; }
        .entry-date { width: 31mm; text-align: right; font-size: 8pt; white-space: nowrap; }
        .entry-sub { margin-top: 0.8mm; }
        .entry-sub td { vertical-align: top; }
        .entry-subtitle { font-size: 9.5pt; color: #40638e; }
        .entry-note { text-align: right; font-size: 8pt; white-space: nowrap; padding-left: 3mm !important; }
        .entry-position { font-size: 8.5pt; font-style: italic; color: #666666; margin-top: 0.4mm; }
        .entry-body { margin-top: 1.3mm; }
        .entry-body p { margin: 0 0 0.8mm; }
        .bullet { padding-left: 3mm; text-indent: -3mm; margin: 0 0 0.3mm; }
        .meta { font-size: 8pt; color: #666666; margin-top: 0.6mm; }
        .links { font-size: 8pt; margin-top: 0.8mm; }

        /* Certificaciones: dos columnas, que en el CV completo son más de sesenta. */
        .certs { width: 100%; }
        .certs td { width: 50%; vertical-align: top; padding: 0 3mm 2mm 2mm; page-break-inside: avoid; }
        .certs .c-title { font-size: 9pt; color: #424242; line-height: 1.25; }
        .certs .c-sub { font-size: 7.5pt; color: #666666; margin-top: 0.3mm; }

        .skills { width: 100%; }
        .skills td { vertical-align: top; padding: 0 0 1.6mm; }
        .skills .s-name { width: 32mm; font-weight: bold; font-size: 9pt; color: #40638e; padding: 0 2mm 1.6mm 2mm; }
        .skills .s-text { font-size: 9pt; }
    </style>
</head>
<body>

{{-- ═══ Barra lateral (primera página) ═══ --}}
<div class="sidebar">
    <div class="sidebar-inner">
        <div class="logo"><img src="{{ $doc->logoPath() }}" alt=""></div>

        @if ($doc->inSidebar('profile'))
            <div class="side-title">Perfil profesional</div>
            @foreach ($doc->profile() as $block)
                @if ($block['type'] === 'list')
                    @foreach ($block['items'] as $item)
                        <p class="side-text">– {{ $item }}</p>
                    @endforeach
                @else
                    <p class="side-text">{{ $block['text'] }}</p>
                @endif
            @endforeach
        @endif

        @if ($doc->inSidebar('skills'))
            <div class="side-title">Habilidades</div>
            @foreach ($skills as $skill)
                @if ($skill['text'])
                    <p class="side-label">{{ $skill['name'] }}</p>
                    <p class="side-list">{{ $skill['text'] }}</p>
                @else
                    <p class="side-list">{{ $skill['name'] }}</p>
                @endif
            @endforeach
        @endif

        @if ($doc->inSidebar('hobbies'))
            <div class="side-title">Intereses</div>
            @foreach ($hobbies as $hobby)
                <p class="side-list">{{ $hobby['title'] }}</p>
            @endforeach
        @endif

        @if ($doc->inSidebar('repositories'))
            <div class="side-title">Código abierto</div>
            @foreach ($repositories as $repository)
                <p class="side-label">{{ $repository['title'] }}</p>
                <p class="side-list"><a href="{{ $repository['url'] }}">{{ $display($repository['url']) }}</a></p>
            @endforeach
        @endif
    </div>

    <div class="side-bottom">
        @if ($qr)
            <div class="qr-box">
                <a href="{{ $doc->publicUrl() }}"><img class="qr" src="{{ $qr }}" alt=""></a>
                <div class="qr-label"><img src="{{ $icon('globe-alt', '#ffffff') }}" alt=""> <a href="{{ $doc->publicUrl() }}">CV Online</a></div>
            </div>
        @endif
        @if ($contact['linkedin'] || $contact['github'])
            <table class="buttons">
                <tr>
                    @if ($contact['linkedin'])
                        <td class="btn-linkedin"><a href="{{ $contact['linkedin'] }}"><span class="in-mark">in</span> LinkedIn</a></td>
                    @endif
                    @if ($contact['github'])
                        <td class="btn-github"><a href="{{ $contact['github'] }}"><img class="gh-mark" src="data:image/svg+xml;base64,{{ base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#ffffff" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>') }}" alt=""> GitHub</a></td>
                    @endif
                </tr>
            </table>
        @endif
    </div>
</div>

{{-- ═══ Columna principal ═══ --}}
<div class="main">
    <div class="name">{{ $doc->name() }}</div>
    <div class="headline">{{ $doc->headline() }}</div>

    <table class="contact">
        <tr>
            <td>
                @if ($contact['email'])
                    <img src="{{ $icon('envelope') }}" alt=""> <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                @endif
            </td>
            <td>
                @if ($contact['website'])
                    <img src="{{ $icon('globe-alt') }}" alt=""> <a href="{{ $contact['website'] }}">{{ $display($contact['website']) }}</a>
                @endif
            </td>
            <td>
                @if ($contact['linkedin'])
                    <img src="{{ $icon('link') }}" alt=""> <a href="{{ $contact['linkedin'] }}">linkedin.com</a>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                @if ($contact['location'])
                    <img src="{{ $icon('map-pin') }}" alt=""> {{ $contact['location'] }}
                @endif
            </td>
            <td colspan="2">
                @if ($contact['details'])
                    <img src="{{ $icon('star') }}" alt=""> {{ implode(' · ', $contact['details']) }}
                @endif
            </td>
        </tr>
    </table>

    @if (! $doc->inSidebar('profile') && filled($doc->profile()))
        <div class="section-title">Perfil profesional</div>
        <div class="entry profile">
            @include('cv.partials.pdf-blocks', ['blocks' => $doc->profile()])
        </div>
    @endif

    @if ($experience)
        <div class="section-title">Experiencia</div>
        @foreach ($experience as $row)
            @include('cv.partials.pdf-entry', ['row' => $row])
        @endforeach
    @endif

    @if (! $doc->inSidebar('skills') && $skills)
        <div class="section-title">Habilidades</div>
        <table class="skills">
            @foreach ($skills as $skill)
                <tr>
                    <td class="s-name">{{ $skill['name'] }}</td>
                    <td class="s-text">{{ $skill['text'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($education)
        <div class="section-title">Educación</div>
        @foreach ($education as $row)
            @include('cv.partials.pdf-entry', ['row' => $row])
        @endforeach
    @endif

    @if ($complementary)
        <div class="section-title">Formación complementaria</div>
        @foreach ($complementary as $row)
            @include('cv.partials.pdf-entry', ['row' => $row, 'compact' => true])
        @endforeach
    @endif

    @if ($certifications)
        <div class="section-title">Certificaciones y cursos</div>
        <table class="certs">
            @foreach (array_chunk($certifications, 2) as $pair)
                <tr>
                    @foreach ($pair as $row)
                        @php
                            $sub = collect([$row['subtitle']])->merge($row['meta'])->push($row['period'])->filter()->implode(' · ');
                        @endphp
                        <td>
                            <div class="c-title">{{ $row['title'] }}</div>
                            @if ($sub !== '')
                                <div class="c-sub">
                                    @if ($row['credential_url'])
                                        <a href="{{ $row['credential_url'] }}">{{ $sub }}</a>
                                    @else
                                        {{ $sub }}
                                    @endif
                                </div>
                            @endif
                            @if ($row['text'] !== '')
                                <div class="c-sub">{{ $row['text'] }}</div>
                            @endif
                        </td>
                    @endforeach
                    @if (count($pair) === 1)
                        <td></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @endif

    @foreach (['Proyectos' => $projects, 'Trabajos' => $jobs, 'Servicios' => $services, 'Colaboraciones' => $collaborations] as $title => $rows)
        @continue(! $rows)
        <div class="section-title">{{ $title }}</div>
        @foreach ($rows as $row)
            @include('cv.partials.pdf-entry', ['row' => $row])
        @endforeach
    @endforeach

    @if ($otherExperience)
        <div class="section-title">Otra experiencia</div>
        @foreach ($otherExperience as $row)
            @include('cv.partials.pdf-entry', ['row' => $row])
        @endforeach
    @endif

    @if (! $doc->inSidebar('hobbies') && $hobbies)
        <div class="section-title">Intereses</div>
        @foreach ($hobbies as $row)
            @include('cv.partials.pdf-entry', ['row' => $row, 'compact' => true])
        @endforeach
    @endif

    @if (! $doc->inSidebar('repositories') && $repositories)
        <div class="section-title">Código abierto</div>
        @foreach ($repositories as $row)
            @include('cv.partials.pdf-entry', ['row' => $row, 'compact' => true])
        @endforeach
    @endif
</div>

</body>
</html>
