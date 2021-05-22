<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="csrf_token" content="{{ csrf_token() }}" />

@yield('meta')

<title>@yield('title', 'APP Admin Panel')</title>
<meta name="description" content="@yield('description', config('app.description'))">
<meta name="author" content="@yield('title', 'Raúl Caro Pastorino')">
<meta name="keywords" content="@yield('keywords', 'App Fryntiz, fryntiz, chipiona, desarrollador web')" />
