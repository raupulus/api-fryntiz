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
    @php
        /*
         * Previsualización del currículum con la misma maquetación que el PDF
         * (la del CV de 2024): columna principal y barra lateral azul. Qué va en
         * cada sitio lo decide CurriculumDocument, igual que en cv/pdf, para
         * que lo que se ve aquí sea lo que se descarga.
         */
        $doc = \App\Services\Cv\CurriculumDocument::for($cv);
        $contact = $doc->contact();
        $qr = $doc->qrCodeDataUri();
        $display = fn (?string $url): string => rtrim((string) preg_replace('#^https?://(www\.)?#', '', (string) $url), '/');

        $skills = $doc->skills();
        $hobbies = $doc->hobbies();
        $repositories = $doc->repositories();
        $certifications = $doc->certifications();

        $sections = [
            'Experiencia' => $doc->experience(),
            'Habilidades' => null,
            'Educación' => $doc->education(),
            'Formación complementaria' => $doc->complementary(),
            'Certificaciones y cursos' => null,
            'Proyectos' => $doc->projects(),
            'Trabajos' => $doc->jobs(),
            'Servicios' => $doc->services(),
            'Colaboraciones' => $doc->collaborations(),
            'Otra experiencia' => $doc->otherExperience(),
        ];
        $compactSections = ['Formación complementaria'];
    @endphp

    {{-- Volver y acciones --}}
    <section class="pt-24 pb-6 bg-surface">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('cv.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container rounded-lg text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Volver a Currículum
            </a>

            {{-- Solo si el CV admite descarga. «Ver» lo abre en el navegador; «Descargar» fuerza la descarga. --}}
            @if($cv->is_downloadable)
                <div class="flex flex-wrap gap-2">
                    <x-button :href="route('cv.pdf', ['slug' => $cv->slug])" variant="outline" icon="picture_as_pdf" target="_blank" rel="noopener">
                        Ver PDF
                    </x-button>
                    <x-button :href="route('cv.pdf', ['slug' => $cv->slug, 'download' => 1])" icon="download">
                        Descargar PDF
                    </x-button>
                </div>
            @endif
        </div>
    </section>

    <section class="pb-16 bg-surface">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <article class="bg-surface-container-lowest rounded-xl shadow-xl overflow-clip grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_20rem]">

                {{-- Cabecera: nombre, titular y contacto --}}
                <header class="px-6 pt-8 md:px-10 md:pt-10 md:col-start-1 md:row-start-1">
                    <h1 class="text-4xl md:text-5xl font-light uppercase tracking-tight text-on-surface">{{ $doc->name() }}</h1>
                    <p class="text-lg text-cv-accent mt-2">{{ $doc->headline() }}</p>

                    <ul class="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-2 text-sm text-on-surface">
                        @if($contact['email'])
                            <li class="flex items-center gap-2 min-w-0"><span class="material-symbols-outlined text-base text-on-surface-variant">mail</span><a href="mailto:{{ $contact['email'] }}" class="underline truncate">{{ $contact['email'] }}</a></li>
                        @endif
                        @if($contact['website'])
                            <li class="flex items-center gap-2 min-w-0"><span class="material-symbols-outlined text-base text-on-surface-variant">language</span><a href="{{ $contact['website'] }}" target="_blank" rel="noopener" class="underline truncate">{{ $display($contact['website']) }}</a></li>
                        @endif
                        @if($contact['linkedin'])
                            <li class="flex items-center gap-2 min-w-0"><span class="material-symbols-outlined text-base text-on-surface-variant">link</span><a href="{{ $contact['linkedin'] }}" target="_blank" rel="noopener" class="underline truncate">linkedin.com</a></li>
                        @endif
                        @if($contact['location'])
                            <li class="flex items-center gap-2 min-w-0"><span class="material-symbols-outlined text-base text-on-surface-variant">location_on</span>{{ $contact['location'] }}</li>
                        @endif
                        @if($contact['details'])
                            <li class="flex items-center gap-2 min-w-0 lg:col-span-2"><span class="material-symbols-outlined text-base text-on-surface-variant">star</span>{{ implode(' · ', $contact['details']) }}</li>
                        @endif
                    </ul>
                </header>

                {{-- Barra lateral --}}
                {{-- En pantalla la barra es tan alta como el documento: su contenido acompaña al desplazarse. --}}
                <aside class="bg-cv-sidebar text-on-cv-sidebar md:col-start-2 md:row-start-1 md:row-span-2 mt-8 md:mt-0">
                  <div class="md:sticky md:top-20">
                    <div class="px-6 pt-8 pb-6">
                        <img src="{{ $cv->url_image }}" alt="{{ $doc->name() }}" class="w-28 h-28 mx-auto object-contain">

                        @if($doc->inSidebar('profile'))
                            <h2 class="mt-6 pb-1 mb-3 border-b border-on-cv-sidebar/80 text-lg font-light uppercase tracking-wide">Perfil profesional</h2>
                            <div class="space-y-2 text-sm leading-relaxed">
                                @include('cv.partials.web-blocks', ['blocks' => $doc->profile()])
                            </div>
                        @endif

                        @if($doc->inSidebar('skills'))
                            <h2 class="mt-6 pb-1 mb-3 border-b border-on-cv-sidebar/80 text-lg font-light uppercase tracking-wide">Habilidades</h2>
                            <dl class="space-y-2 text-sm">
                                @foreach($skills as $skill)
                                    <div>
                                        <dt class="text-xs font-bold uppercase tracking-wide">{{ $skill['name'] }}</dt>
                                        @if($skill['text'])<dd>{{ $skill['text'] }}</dd>@endif
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        @if($doc->inSidebar('hobbies'))
                            <h2 class="mt-6 pb-1 mb-3 border-b border-on-cv-sidebar/80 text-lg font-light uppercase tracking-wide">Intereses</h2>
                            <ul class="space-y-1.5 text-sm">
                                @foreach($hobbies as $hobby)
                                    <li>{{ $hobby['title'] }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if($doc->inSidebar('repositories'))
                            <h2 class="mt-6 pb-1 mb-3 border-b border-on-cv-sidebar/80 text-lg font-light uppercase tracking-wide">Código abierto</h2>
                            <ul class="space-y-2 text-sm">
                                @foreach($repositories as $repository)
                                    <li>
                                        <span class="block text-xs font-bold uppercase tracking-wide">{{ $repository['title'] }}</span>
                                        <a href="{{ $repository['url'] }}" target="_blank" rel="noopener" class="hover:underline">{{ $display($repository['url']) }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        @if($qr)
                            <div class="bg-cv-sidebar-footer text-center py-5">
                                <img src="{{ $qr }}" alt="Código QR con el enlace a este currículum" class="w-28 h-28 mx-auto bg-white p-1">
                                <p class="mt-2 inline-flex items-center gap-1"><span class="material-symbols-outlined text-base">language</span>CV Online</p>
                            </div>
                        @endif
                        @if($contact['linkedin'] || $contact['github'])
                            <div class="grid grid-cols-2 text-sm font-bold text-white">
                                @if($contact['linkedin'])
                                    <a href="{{ $contact['linkedin'] }}" target="_blank" rel="noopener" class="bg-cv-linkedin py-3 text-center hover:opacity-90">LinkedIn</a>
                                @endif
                                @if($contact['github'])
                                    <a href="{{ $contact['github'] }}" target="_blank" rel="noopener" class="bg-cv-github py-3 text-center hover:opacity-90">GitHub</a>
                                @endif
                            </div>
                        @endif
                    </div>
                  </div>
                </aside>

                {{-- Columna principal --}}
                <div class="px-6 pb-10 md:px-10 md:col-start-1 md:row-start-2">
                    @if(! $doc->inSidebar('profile') && filled($doc->profile()))
                        <h2 class="mt-8 pb-1 mb-4 border-b border-outline-variant text-2xl font-light uppercase text-on-surface">Perfil profesional</h2>
                        <div class="space-y-3 text-sm text-on-surface sm:pl-2">
                            @include('cv.partials.web-blocks', ['blocks' => $doc->profile()])
                        </div>
                    @endif

                    @foreach($sections as $title => $rows)
                        @if($title === 'Habilidades')
                            @if(! $doc->inSidebar('skills') && $skills)
                                <h2 class="mt-8 pb-1 mb-4 border-b border-outline-variant text-2xl font-light uppercase text-on-surface">Habilidades</h2>
                                <dl class="grid grid-cols-1 sm:grid-cols-[11rem_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm sm:pl-2">
                                    @foreach($skills as $skill)
                                        <dt class="font-bold text-cv-accent">{{ $skill['name'] }}</dt>
                                        <dd class="text-on-surface mb-1 sm:mb-0">{{ $skill['text'] }}</dd>
                                    @endforeach
                                </dl>
                            @endif
                            @continue
                        @endif

                        @if($title === 'Certificaciones y cursos')
                            @if($certifications)
                                <h2 class="mt-8 pb-1 mb-4 border-b border-outline-variant text-2xl font-light uppercase text-on-surface">Certificaciones y cursos</h2>
                                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 sm:pl-2">
                                    @foreach($certifications as $row)
                                        @php $sub = collect([$row['subtitle']])->merge($row['meta'])->push($row['period'])->filter()->implode(' · '); @endphp
                                        <li>
                                            <p class="text-sm text-on-surface">{{ $row['title'] }}</p>
                                            @if($sub !== '')
                                                <p class="text-xs text-on-surface-variant">
                                                    @if($row['credential_url'])
                                                        <a href="{{ $row['credential_url'] }}" target="_blank" rel="noopener" class="text-cv-accent hover:underline">{{ $sub }}</a>
                                                    @else
                                                        {{ $sub }}
                                                    @endif
                                                </p>
                                            @endif
                                            @if($row['text'] !== '')
                                                <p class="text-xs text-on-surface-variant">{{ $row['text'] }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @continue
                        @endif

                        @continue(! $rows)
                        <h2 class="mt-8 pb-1 mb-4 border-b border-outline-variant text-2xl font-light uppercase text-on-surface">{{ $title }}</h2>
                        <div class="space-y-5">
                            @foreach($rows as $row)
                                @include('cv.partials.web-entry', ['row' => $row, 'compact' => in_array($title, $compactSections, true)])
                            @endforeach
                        </div>
                    @endforeach

                    @if(! $doc->inSidebar('hobbies') && $hobbies)
                        <h2 class="mt-8 pb-1 mb-4 border-b border-outline-variant text-2xl font-light uppercase text-on-surface">Intereses</h2>
                        <div class="space-y-3">
                            @foreach($hobbies as $row)
                                @include('cv.partials.web-entry', ['row' => $row, 'compact' => true])
                            @endforeach
                        </div>
                    @endif

                    @if(! $doc->inSidebar('repositories') && $repositories)
                        <h2 class="mt-8 pb-1 mb-4 border-b border-outline-variant text-2xl font-light uppercase text-on-surface">Código abierto</h2>
                        <div class="space-y-3">
                            @foreach($repositories as $row)
                                @include('cv.partials.web-entry', ['row' => $row, 'compact' => true])
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        </div>
    </section>
@endsection
