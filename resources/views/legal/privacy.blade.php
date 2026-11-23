@extends('layouts.app')

@section('title', 'Política de Privacidad | Api Raupulus')
@section('description', 'Información sobre el tratamiento de datos personales, formularios de contacto, newsletter y derechos RGPD en Api Raupulus.')

@section('content')
    <section class="hero-gradient min-h-[30svh] flex items-center pt-20 pb-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 text-on-primary w-full">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tighter mb-3">Política de Privacidad</h1>
            <p class="text-lg text-on-primary/85">Tratamiento de datos personales conforme al RGPD y la LOPDGDD.</p>
        </div>
    </section>

    <section class="py-12 bg-surface">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <article class="bg-surface-container-lowest rounded-xl shadow-lg p-6 sm:p-8 space-y-6 text-on-surface-variant leading-relaxed">
                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">1. Responsable del tratamiento</h2>
                    <p>El responsable del tratamiento de los datos recabados a través de esta plataforma es <strong>Raúl Caro Pastorino (@raupulus)</strong>, con dirección de contacto en <a href="mailto:public@raupulus.dev" class="underline text-primary font-semibold">public@raupulus.dev</a>.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">2. Finalidad y base jurídica</h2>
                    <ul class="list-disc pl-5 space-y-1.5">
                        <li><strong>Formularios de contacto:</strong> atender mensajes enviados voluntariamente con base en el consentimiento expreso del interesado (art. 6.1.a RGPD).</li>
                        <li><strong>Newsletter:</strong> gestionar suscripciones, verificación por doble opt-in y bajas inmediatas en un clic (art. 6.1.a RGPD).</li>
                        <li><strong>Seguridad y prevención de abuso:</strong> validación anti-bot y control de tasa de peticiones por interés legítimo (art. 6.1.f RGPD).</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">3. Conservación y destinatarios</h2>
                    <p>No se ceden ni venden datos personales a terceros. En el visor cartográfico de tráfico aéreo (<code class="font-mono text-xs">/airflight</code>), la carga del mapa base realiza peticiones directas desde el navegador hacia los servidores de teselas de OpenStreetMap.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">4. Derechos</h2>
                    <p>Puedes ejercer tus derechos de acceso, rectificación, supresión, oposición, limitación y portabilidad escribiendo a <a href="mailto:public@raupulus.dev" class="underline text-primary font-semibold">public@raupulus.dev</a>, o darte de baja al instante mediante el enlace incluido en cada correo de la newsletter.</p>
                </div>
            </article>
        </div>
    </section>
@endsection
