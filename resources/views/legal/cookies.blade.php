@extends('layouts.app')

@section('title', 'Política de Cookies | Api Raupulus')
@section('description', 'Información clara sobre el uso exclusivo de cookies técnicas de sesión y almacenamiento local en Api Raupulus.')

@section('content')
    <section class="hero-gradient min-h-[30svh] flex items-center pt-20 pb-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 text-on-primary w-full">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tighter mb-3">Política de Cookies</h1>
            <p class="text-lg text-on-primary/85">Uso exclusivo de cookies técnicas esenciales y almacenamiento local sin seguimiento.</p>
        </div>
    </section>

    <section class="py-12 bg-surface">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <article class="bg-surface-container-lowest rounded-xl shadow-lg p-6 sm:p-8 space-y-6 text-on-surface-variant leading-relaxed">
                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">1. Cero cookies analíticas o publicitarias</h2>
                    <p>Este sitio web <strong>no utiliza cookies analíticas, publicitarias ni de seguimiento entre sitios</strong>.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">2. Cookies técnicas estrictamente necesarias</h2>
                    <ul class="list-disc pl-5 space-y-1.5">
                        <li><strong>Cookie de sesión y token CSRF (<code class="font-mono text-xs">XSRF-TOKEN</code>):</strong> imprescindibles para proteger los envíos POST (por ejemplo, confirmación o baja en newsletter) y el acceso autenticado en los paneles privados.</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">3. Almacenamiento local (<code class="font-mono text-xs">localStorage</code>)</h2>
                    <p>Se guarda únicamente la preferencia visual elegida por el usuario bajo la clave <code class="font-mono text-xs">theme</code> (<code class="font-mono text-xs">light</code> o <code class="font-mono text-xs">dark</code>) para mantener el tema seleccionado entre visitas sin enviar información a terceros.</p>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-on-surface mb-2">4. Contacto</h2>
                    <p>Para cualquier duda sobre esta política, puedes escribir a <a href="mailto:public@raupulus.dev" class="underline text-primary font-semibold">public@raupulus.dev</a>.</p>
                </div>
            </article>
        </div>
    </section>
@endsection
