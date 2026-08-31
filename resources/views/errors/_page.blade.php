{{--
    Cuerpo común de las páginas de error.

    Antes cada código iba por su cuenta: 404 y 500 extendían `layouts.app` pero
    con clases de Bootstrap (`row`, `col-12`, `display-1`, `lead`) que en este
    proyecto no existen —es Tailwind 4—, y 401, 403, 419, 429 y 503 extendían
    `errors::minimal`, la plantilla pelada que trae Laravel: fondo blanco,
    tipografía del sistema y ni cabecera ni pie. Siete páginas, tres diseños
    distintos y ninguno el del sitio.

    @param string $code   El número: 404, 500…
    @param string $title   Titular corto.
    @param string $message  Una frase explicando qué ha pasado.
--}}
<section class="min-h-[60vh] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-xl text-center">
        <p class="text-7xl sm:text-8xl font-black tracking-tight text-primary-container/80 select-none">
            {{ $code }}
        </p>

        <h1 class="mt-4 text-2xl sm:text-3xl font-bold text-on-surface">
            {{ $title }}
        </h1>

        <p class="mt-3 text-base text-on-surface-variant">
            {{ $message }}
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('home') }}"
               class="inline-flex items-center rounded-lg bg-primary-container px-5 py-2.5 text-sm font-semibold text-on-primary transition hover:opacity-90">
                Ir a la portada
            </a>

            {{-- Sin `javascript:history.back()`: no funciona con JS desactivado
                 y deja una URL rara en la barra de direcciones. --}}
            <button type="button"
                    onclick="history.back()"
                    class="inline-flex items-center rounded-lg border border-outline px-5 py-2.5 text-sm font-semibold text-on-surface transition hover:bg-surface-container">
                Volver atrás
            </button>
        </div>
    </div>
</section>
