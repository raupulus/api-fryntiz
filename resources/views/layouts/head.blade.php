<meta charset="utf-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

{{-- Título, 50-60 carácteres máximo --}}
<title>@yield('title', config('app.name'))</title>

<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="csrf_token" content="{{ csrf_token() }}" />

{{-- Idioma del contenido --}}
<meta property="og:locale" content="@yield('meta-og-locale', 'es_ES')">
<meta http-equiv="Content-Language" content="es" />

{{-- Alcance para distribuir el contenido --}}
<meta name="distribution" content="global" />

@yield('meta')

<meta name="language" content="@yield('meta-og-locale', 'Spanish')">
<meta name="description" content="@yield('meta-description', config('app.description'))" /> {{-- Máximo 150-160 carácteres --}}
<meta name="author" content="@yield('meta-author', 'Raúl Caro Pastorino')" />
<meta name="copyright" content="@yield('meta-author', 'Raúl Caro Pastorino')" />
<meta name="robots" content="@yield('meta-robots', 'index, follow')" />
<meta name="keywords" content="@yield('meta-keywords', 'Api Raupulus, raupulus, chipiona, desarrollador web, developer, backend, backend developer, Raúl Caro Pastorino')" />

{{-- <meta name="revised" content="Sunday, July 18th, 2010, 5:15 pm" /> --}}
{{-- <meta name="rating" content="General"> --}}

{{-- <meta property="article:published_time" content="2013-09-17T05:59:00+01:00" /> --}}
{{-- <meta property="article:modified_time" content="2013-09-16T19:08:47+01:00" /> --}}

{{-- No Cachear --}}
{{--
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="Thu, 01 Dec 1994 16:00:00 GMT">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
--}}

<meta name="url" content="{{request()->fullUrl()}}">
<meta name="identifier-URL" content="{{request()->fullUrl()}}">
<meta name="revisit-after" content="7 days">



{{-- Redes sociales --}}
<meta property="og:title"
      content="@yield('meta-og-title', config('app.name'))" />
<meta property="og:site_name"
      content="@yield('meta-og-site_name', config('app.name'))" />
<meta property="og:type" content="@yield('og-type', 'website')" />
<meta property="og:description"
      content="@yield('meta-og-description', config('app.description'))" />
<meta property="og:image"
      content="@yield('meta-og-image', asset('images/logo/logo640x640.webp'))" />
<meta property="og:image:url"
      content="@yield('meta-og-image-url', asset('images/logo/logo640x640.webp'))" />
<meta property="og:image:secure_url"
      content="@yield('meta-og-image-secure_url', asset('images/logo/logo640x640.webp'))" />
<meta property="og:url"
      content="@yield('meta-og-url', request()->fullUrl())" />
<meta property="og:image:alt"
      content="@yield('meta-og-image_alt', config('app.description'))" />
<meta property="og:image:type"
      content="@yield('meta-og-image-type', 'image/webp')" />

<meta name="twitter:title" content="@yield('meta-twitter-title', config('app.name'))" />
<meta name="twitter:card" content="@yield('meta-twitter-card', 'summary')" />
<meta name="twitter:site" content="@yield('meta-twitter-site', '@raupulus')" />
<meta name="twitter:creator" content="@yield('meta-twitter-creator', '@raupulus')" />

{{-- Iconos --}}
{{-- TODO → Dinamizar para reescribirlo desde cada parte de la api --}}
<link rel="apple-touch-icon" sizes="57x57" href="{{asset('images/favicons/apple-icon-57x57.png')}}" />
<link rel="apple-touch-icon" sizes="60x60" href="{{asset('images/favicons/apple-icon-60x60.png')}}" />
<link rel="apple-touch-icon" sizes="72x72" href="{{asset('images/favicons/apple-icon-72x72.png')}}" />
<link rel="apple-touch-icon" sizes="76x76" href="{{asset('images/favicons/apple-icon-76x76.png')}}" />
<link rel="apple-touch-icon" sizes="114x114" href="{{asset('images/favicons/apple-icon-114x114.png')}}" />
<link rel="apple-touch-icon" sizes="120x120" href="{{asset('images/favicons/apple-icon-120x120.png')}}" />
<link rel="apple-touch-icon" sizes="144x144" href="{{asset('images/favicons/apple-icon-144x144.png')}}" />
<link rel="apple-touch-icon" sizes="152x152" href="{{asset('images/favicons/apple-icon-152x152.png')}}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{asset('images/favicons/apple-icon-180x180.png')}}" />
<link rel="icon" type="image/png" sizes="192x192"  href="{{asset('images/favicons/android-icon-192x192.png')}}" />
<link rel="icon" type="image/png" sizes="32x32" href="{{asset('images/favicons/favicon-32x32.png')}}" />
<link rel="icon" type="image/png" sizes="96x96" href="{{asset('images/favicons/favicon-96x96.png')}}" />
<link rel="icon" type="image/png" sizes="16x16" href="{{asset('images/favicons/favicon-16x16.png')}}" />
<meta name="msapplication-TileImage" content="{{asset('images/favicons/ms-icon-144x144.png')}}" />
