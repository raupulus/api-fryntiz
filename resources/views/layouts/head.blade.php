<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="csrf_token" content="{{ csrf_token() }}" />

{{-- Idioma del contenido --}}
<meta property="og:locale" content="@yield('meta-og-locale', 'es_ES')">
<meta http-equiv="Content-Language" content="es"/>

{{-- Alcance para distribuir el contenido --}}
<meta name="distribution" content="global" />

@yield('meta')

<title>@yield('title', config('app.name'))</title>
<meta name="description" content="@yield('meta-description', config('app.description'))">
<meta name="author" content="@yield('meta-author', 'Raúl Caro Pastorino')">
<meta name="copyright" content="@yield('meta-author', 'Raúl Caro Pastorino')" />
<meta name="robots" content="@yield('meta-robots', 'index,follow')">
<meta name="keywords" content="@yield('meta-keywords', 'Api Fryntiz, fryntiz, chipiona, desarrollador web')" />

{{-- Redes sociales --}}
<meta property="og:title"
      content="@yield('meta-og-title', config('app.name'))">
<meta property="og:site_name"
      content="@yield('meta-og-site_name', config('app.name'))">
<meta property="og:type" content="@yield('og-type', 'website')">
<meta property="og:description"
      content="@yield('meta-og-description', config('app.description'))">
<meta property="og:image"
      content="@yield('meta-og-image', asset('images/redes-sociales/rs-1200x1200.png')))">
<meta property="og:url"
      content="@yield('meta-og-url', config('app.name'))">
<meta property="og:image:alt"
      content="@yield('meta-og-image_alt', config('app.description'))">

<meta name="twitter:card" content="@yield('meta-twitter-card', 'summary')">
<meta name="twitter:site" content="@yield('meta-twitter-site', '@fryntiz')">
<meta name="twitter:creator" content="@yield('meta-twitter-creator', '@fryntiz')">

{{-- Iconos --}}
<link rel="apple-touch-icon" sizes="180x180" href="{{asset('images/logos/180x180.png')}}">
<link rel="icon" type="image/png" sizes="64x64" href="{{asset('images/logos/64x64.png')}}">
<link rel="icon" type="image/png" sizes="32x32" href="{{asset('images/logos/32x32.png')}}">
<link rel="icon" type="image/png" sizes="16x16" href="{{asset('images/logos/16x16.png')}}">
