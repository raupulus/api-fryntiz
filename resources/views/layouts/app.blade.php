<!DOCTYPE html>
<html class="scroll-smooth" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Api Raupulus | Raúl Caro Pastorino')</title>
    @php
        $metaDescription = trim($__env->yieldContent('description', 'Plataforma multi-API en Laravel para monitorización IoT, estación meteorológica en Chipiona, vuelos ADS-B y currículums públicos.'));
        $metaKeywords = trim($__env->yieldContent('keywords'));
        $ogImage = trim($__env->yieldContent('rs-image')) ?: asset('images/social-thumbnail.jpg');
        $ogImageType = trim($__env->yieldContent('rs-image-type'));
        $ogImageWidth = trim($__env->yieldContent('rs-image-width'));
        $ogImageHeight = trim($__env->yieldContent('rs-image-height'));
        $ogType = trim($__env->yieldContent('rs-type', 'website'));
        $ogTitle = trim($__env->yieldContent('rs-title')) ?: trim($__env->yieldContent('title', 'Api Raupulus'));
        $ogDescription = trim($__env->yieldContent('rs-description')) ?: $metaDescription;
        $ogImageAlt = trim($__env->yieldContent('rs-image-alt')) ?: $ogTitle;
        $twitterTitle = trim($__env->yieldContent('meta-twitter-title')) ?: $ogTitle;
        $twitterDescription = trim($__env->yieldContent('meta-twitter-description')) ?: $ogDescription;
    @endphp
    <meta name="description" content="{{ $metaDescription }}"/>
    @if ($metaKeywords !== '')
        <meta name="keywords" content="{{ $metaKeywords }}"/>
    @endif

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $ogType }}"/>
    <meta property="og:locale" content="es_ES"/>
    <meta property="og:title" content="{{ $ogTitle }}"/>
    <meta property="og:site_name" content="@yield('rs-sitename', 'Api Raupulus')"/>
    <meta property="og:description" content="{{ $ogDescription }}"/>
    <meta property="og:url" content="@yield('rs-url', url()->current())"/>
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
    <meta property="og:image:alt" content="{{ $ogImageAlt }}"/>

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image"/>
    <meta name="twitter:title" content="{{ $twitterTitle }}"/>
    <meta name="twitter:description" content="{{ $twitterDescription }}"/>
    <meta name="twitter:creator" content="@raupulus"/>
    <meta name="twitter:image" content="{{ $ogImage }}"/>
    <meta name="twitter:image:alt" content="{{ $ogImageAlt }}"/>

    {{-- Indexación y Canonical --}}
    @hasSection('noindex')
        <meta name="robots" content="noindex, nofollow"/>
    @else
        <link rel="canonical" href="@yield('canonical', url()->current())"/>
    @endif

    {{-- Favicons y Manifiesto PWA --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any"/>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicons/favicon-32x32.png') }}?v=2"/>
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/favicons/favicon-96x96.png') }}?v=2"/>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicons/apple-icon-180x180.png') }}?v=2"/>
    <link rel="manifest" href="{{ asset('site.webmanifest') }}"/>

    @yield('meta')

    {{-- Anti-FOUC: aplicar tema oscuro ANTES de que cargue cualquier CSS --}}
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('json-ld')
    @stack('head')
    @stack('styles')
</head>
{{--
    Alpine.js v3 solo inicializa desde document.body hacia abajo.
    Por eso darkMode va en body, con $watch que actualiza la clase en <html>.
--}}
<body class="bg-background text-on-background font-body antialiased min-h-dvh flex flex-col"
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
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-lg focus:bg-primary-container focus:text-on-primary focus:font-semibold">
        Saltar al contenido principal
    </a>
    <x-navbar />
    <main id="main-content" class="flex-1">
        @yield('content')
    </main>
    <x-footer />
    @stack('scripts')
</body>
</html>
