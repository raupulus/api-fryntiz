<!DOCTYPE html>
<html class="scroll-smooth" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Api Raupulus | Raul Caro Pastorino')</title>
    <meta name="description" content="@yield('description', 'Api Raupulus - Plataforma de APIs y servicios IoT')"/>
    <meta name="keywords" content="@yield('keywords', '')"/>

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('rs-title', 'Api Raupulus')"/>
    <meta property="og:site_name" content="@yield('rs-sitename', 'Api Raupulus')"/>
    <meta property="og:description" content="@yield('rs-description', '')"/>
    <meta property="og:image" content="@yield('rs-image', '')"/>
    <meta property="og:url" content="@yield('rs-url', '')"/>
    <meta property="og:image:alt" content="@yield('rs-image-alt', '')"/>

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image"/>
    <meta name="twitter:title" content="@yield('meta-twitter-title', 'Api Raupulus')"/>

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
