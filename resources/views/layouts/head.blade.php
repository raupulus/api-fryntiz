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
<meta name="keywords" content="@yield('meta-keywords', 'Api Fryntiz, fryntiz, chipiona, desarrollador web, Raúl Caro Pastorino')" />

{{-- Redes sociales --}}
<meta property="og:title"
      content="@yield('meta-og-title', config('app.name'))">
<meta property="og:site_name"
      content="@yield('meta-og-site_name', config('app.name'))">
<meta property="og:type" content="@yield('og-type', 'website')">
<meta property="og:description"
      content="@yield('meta-og-description', config('app.description'))">
<meta property="og:image"
      content="@yield('meta-og-image', asset('images/logo-fryntiz-512x512.png)))">
<meta property="og:url"
      content="@yield('meta-og-url', config('app.name'))">
<meta property="og:image:alt"
      content="@yield('meta-og-image_alt', config('app.description'))">

<meta name="twitter:card" content="@yield('meta-twitter-title', config('app.name'))">
<meta name="twitter:card" content="@yield('meta-twitter-card', 'summary')">
<meta name="twitter:site" content="@yield('meta-twitter-site', '@fryntiz')">
<meta name="twitter:creator" content="@yield('meta-twitter-creator', '@fryntiz')">

{{-- Iconos --}}
{{-- TODO → Dinamizar para reescribirlo desde cada parte de la api --}}
<link rel="apple-touch-icon" sizes="57x57" href="{{asset('images/favicons/apple-icon-57x57.png')}}">
<link rel="apple-touch-icon" sizes="60x60" href="{{asset('images/favicons/apple-icon-60x60.png')}}">
<link rel="apple-touch-icon" sizes="72x72" href="{{asset('images/favicons/apple-icon-72x72.png')}}">
<link rel="apple-touch-icon" sizes="76x76" href="{{asset('images/favicons/apple-icon-76x76.png')}}">
<link rel="apple-touch-icon" sizes="114x114" href="{{asset('images/favicons/apple-icon-114x114.png')}}">
<link rel="apple-touch-icon" sizes="120x120" href="{{asset('images/favicons/apple-icon-120x120.png')}}">
<link rel="apple-touch-icon" sizes="144x144" href="{{asset('images/favicons/apple-icon-144x144.png')}}">
<link rel="apple-touch-icon" sizes="152x152" href="{{asset('images/favicons/apple-icon-152x152.png')}}">
<link rel="apple-touch-icon" sizes="180x180" href="{{asset('images/favicons/apple-icon-180x180.png')}}">
<link rel="icon" type="image/png" sizes="192x192"  href="{{asset('images/favicons/android-icon-192x192.png')}}">
<link rel="icon" type="image/png" sizes="32x32" href="{{asset('images/favicons/favicon-32x32.png')}}">
<link rel="icon" type="image/png" sizes="96x96" href="{{asset('images/favicons/favicon-96x96.png')}}">
<link rel="icon" type="image/png" sizes="16x16" href="{{asset('images/favicons/favicon-16x16.png')}}">
<meta name="msapplication-TileImage" content="{{asset('images/favicons/ms-icon-144x144.png')}}">
