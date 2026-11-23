@extends('layouts.app')

@section('title', 'Aviso Legal | Api Raupulus')
@section('description', 'Información identificativa del titular, condiciones de uso y propiedad intelectual de la plataforma Api Raupulus.')

@section('content')
    <section class="hero-gradient min-h-[30svh] flex items-center pt-20 pb-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 text-on-primary w-full">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tighter mb-3">Aviso Legal</h1>
            <p class="text-lg text-on-primary/85">Información general y condiciones de uso conforme a la LSSI-CE.</p>
        </div>
    </section>

    <section class="py-12 bg-surface">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <article class="bg-surface-container-lowest rounded-xl shadow-lg p-6 sm:p-8 space-y-6 text-on-surface-variant leading-relaxed">
                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">1. Titularidad del sitio web</h2>
                    <p>En cumplimiento de la Ley 34/2002 (LSSI-CE), se informa de que el presente sitio web es titularidad de <strong>Raúl Caro Pastorino (@raupulus)</strong>.</p>
                    <p class="mt-2">Contacto público para consultas, incidencias o avisos técnicos: <a href="mailto:public@raupulus.dev" class="underline text-primary font-semibold">public@raupulus.dev</a>.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">2. Objeto y finalidad</h2>
                    <p>Api Raupulus centraliza experimentos técnicos personales, telemetría IoT (estación meteorológica en Chipiona, sensores ambientales, seguimiento ADS-B, dispositivos hardware y energía solar), gestión documental CMS y currículums públicos con fines divulgativos y de depuración.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">3. Propiedad intelectual y código abierto</h2>
                    <p>Salvo mención expresa en contrario, los desarrollos de código fuente vinculados a los proyectos propios se publican bajo sus respectivas licencias libres en los repositorios públicos indicados. Los contenidos editoriales y datos experimentales no garantizan exactitud comercial ni disponibilidad ininterrumpida.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">4. Enlaces externos</h2>
                    <p>Algunas páginas incluyen enlaces hacia plataformas externas (GitLab, GitHub, AEMET, Open-Meteo, GDACS, FlightRadar24 o raupulus.dev). El titular no asume responsabilidad sobre contenidos o políticas de dichos terceros.</p>
                </div>
            </article>
        </div>
    </section>
@endsection
