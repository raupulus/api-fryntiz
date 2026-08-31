<!DOCTYPE html>
<html class="scroll-smooth" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Api Raupulus | Raul Caro Pastorino')</title>
    <meta name="description" content="@yield('description', 'Api Raupulus - Plataforma de APIs y servicios IoT')"/>
    <meta name="keywords" content="@yield('keywords', '')"/>

    {{--
        Open Graph.

        Las etiquetas de imagen se envían SÓLO si hay imagen (B9). Antes salían
        siempre, vacías: `og:image:type`, `og:image:width`, `og:image:height` y
        `twitter:image` iban en blanco en todas las páginas, y por eso al
        compartir un enlace no aparecía la miniatura. Una etiqueta vacía
        confunde al rastreador más que la ausencia de la etiqueta.
    --}}
    @php
        $ogImage = trim($__env->yieldContent('rs-image'));
        $ogImageType = trim($__env->yieldContent('rs-image-type'));
        $ogImageWidth = trim($__env->yieldContent('rs-image-width'));
        $ogImageHeight = trim($__env->yieldContent('rs-image-height'));
        $ogImageAlt = trim($__env->yieldContent('rs-image-alt'));
    @endphp

    <meta property="og:title" content="@yield('rs-title', 'Api Raupulus')"/>
    <meta property="og:site_name" content="@yield('rs-sitename', 'Api Raupulus')"/>
    <meta property="og:description" content="@yield('rs-description', '')"/>
    <meta property="og:url" content="@yield('rs-url', url()->current())"/>

    @if ($ogImage !== '')
        <meta property="og:image" content="{{ $ogImage }}"/>
        @if ($ogImageType !== '')
            <meta property="og:image:type" content="{{ $ogImageType }}"/>
        @endif
        @if ($ogImageWidth !== '')
            <meta property="og:image:width" content="{{ $ogImageWidth }}"/>
        @endif
        @if ($ogImageHeight !== '')
            <meta property="og:image:height" content="{{ $ogImageHeight }}"/>
        @endif
        @if ($ogImageAlt !== '')
            <meta property="og:image:alt" content="{{ $ogImageAlt }}"/>
        @endif
    @endif

    {{-- Twitter. Sin imagen, la tarjeta grande no tiene sentido: se degrada. --}}
    <meta name="twitter:card" content="{{ $ogImage !== '' ? 'summary_large_image' : 'summary' }}"/>
    <meta name="twitter:title" content="@yield('meta-twitter-title', 'Api Raupulus')"/>
    <meta name="twitter:creator" content="@raupulus"/>
    @if ($ogImage !== '')
        <meta name="twitter:image" content="{{ $ogImage }}"/>
    @endif

    {{-- Canonical --}}
    <link rel="canonical" href="{{ url()->current() }}"/>

    @yield('meta')

    {{-- Anti-FOUC: aplicar tema oscuro ANTES de que cargue cualquier CSS --}}
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @stack('styles')
</head>
{{--
    Alpine.js v3 solo inicializa desde document.body hacia abajo.
    Por eso darkMode va en body, con $watch que actualiza la clase en <html>.
--}}
<body class="bg-background text-on-background font-body antialiased min-h-screen flex flex-col"
      x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }"
      x-init="
          $watch('darkMode', val => {
              localStorage.setItem('theme', val ? 'dark' : 'light');
              if (val) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          });
          /* Sincronizar clase inicial (si anti-FOUC ya la puso) */
          if (darkMode) {
              document.documentElement.classList.add('dark');
          } else {
              document.documentElement.classList.remove('dark');
          }
      ">
    <x-navbar />
    <main class="flex-1">
        @yield('content')
    </main>
    <x-footer />
    @stack('scripts')
</body>
</html>
