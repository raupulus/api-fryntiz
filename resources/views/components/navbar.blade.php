@php
    $navItems = [
        ['label' => 'Weather Station', 'route' => 'weather_station.index', 'pattern' => 'weather_station.*'],
        ['label' => 'Keycounter', 'route' => 'keycounter.index', 'pattern' => 'keycounter.*'],
        ['label' => 'Airflight', 'route' => 'airflight.index', 'pattern' => 'airflight.*'],
        ['label' => 'Smart Plant', 'route' => 'smartplant.index', 'pattern' => 'smartplant.*'],
        ['label' => 'Energy', 'route' => 'hardware.energy.index', 'pattern' => 'hardware.energy.*'],
        ['label' => 'Hardware', 'route' => 'hardware.index', 'pattern' => 'hardware.index'],
        ['label' => 'Currículum', 'route' => 'cv.index', 'pattern' => 'cv.*'],
    ];
@endphp

<header class="fixed top-0 w-full z-50 glass-nav shadow-sm print:hidden"
        x-data="{ mobileMenuOpen: false }"
        x-effect="document.body.classList.toggle('overflow-hidden', mobileMenuOpen)"
        @keydown.escape.window="mobileMenuOpen = false"
        @click.outside="mobileMenuOpen = false">
    <div class="flex justify-between items-center px-4 sm:px-6 py-3 max-w-7xl mx-auto w-full">
        {{-- Logo + Título: en desktop se muestra logo + texto, en mobile solo logo --}}
        <a class="flex items-center gap-3 shrink-0 min-h-11" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>
            <img src="{{ asset('images/logo/logo128x128.webp') }}"
                 alt="Logo Api Raupulus"
                 width="40"
                 height="40"
                 class="w-10 h-10" />
            <span class="text-xl font-bold tracking-tighter text-on-surface hidden sm:inline">
                Api Raupulus
            </span>
        </a>

        {{-- Navegación desktop — visible desde lg en adelante --}}
        <nav aria-label="Navegación principal" class="hidden lg:flex items-center gap-4 xl:gap-5">
            @foreach($navItems as $item)
                @php $isActive = request()->routeIs($item['pattern']); @endphp
                <a class="font-body text-sm font-bold tracking-tight whitespace-nowrap py-1.5 transition-colors {{ $isActive ? 'text-on-surface underline decoration-2 underline-offset-4' : 'text-on-surface-variant hover:text-on-surface' }}"
                   href="{{ route($item['route']) }}"
                   @if($isActive) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            <a class="text-on-surface-variant hover:text-on-surface transition-colors font-body text-sm font-bold tracking-tight whitespace-nowrap py-1.5"
               href="https://raupulus.dev/contact" target="_blank" rel="noopener noreferrer">Contacto</a>
        </nav>

        {{-- Controles: selector tema + hamburguesa (solo mobile y tablet hasta lg) --}}
        <div class="flex items-center gap-1.5 shrink-0">
            {{-- Selector de tema (siempre visible, sin FOUC gracias a dark:hidden / dark:inline) --}}
            <button type="button"
                    @click="darkMode = !darkMode"
                    class="min-w-11 min-h-11 inline-flex items-center justify-center rounded-lg hover:bg-surface-container transition-colors"
                    :aria-label="darkMode ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
                    :title="darkMode ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'">
                <span class="dark:hidden inline-flex items-center">
                    <span class="material-symbols-outlined text-on-surface-variant" aria-hidden="true">dark_mode</span>
                </span>
                <span class="hidden dark:inline-flex items-center">
                    <span class="material-symbols-outlined text-on-surface-variant" aria-hidden="true">light_mode</span>
                </span>
            </button>

            {{-- Hamburguesa SOLO en mobile y tablet (oculto en lg+) --}}
            <button type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden min-w-11 min-h-11 inline-flex items-center justify-center rounded-lg hover:bg-surface-container transition-colors"
                    aria-label="Menú de navegación"
                    aria-controls="mobile-primary-nav"
                    :aria-expanded="mobileMenuOpen.toString()"
                    title="Menú">
                <span class="material-symbols-outlined text-on-surface-variant" x-show="!mobileMenuOpen" aria-hidden="true">menu</span>
                <span class="material-symbols-outlined text-on-surface-variant" x-show="mobileMenuOpen" x-cloak aria-hidden="true">close</span>
            </button>
        </div>
    </div>

    {{-- Menú mobile desplegable (oculto en lg+) --}}
    <nav id="mobile-primary-nav"
         aria-label="Navegación móvil"
         x-show="mobileMenuOpen"
         x-cloak
         x-transition
         class="lg:hidden bg-surface border-t border-outline-variant/15 px-4 sm:px-6 py-3 space-y-1 max-h-[calc(100dvh-4.5rem)] overflow-y-auto pb-[max(1rem,env(safe-area-inset-bottom))]">
        @foreach($navItems as $item)
            @php $isActive = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}"
               @click="mobileMenuOpen = false"
               class="block font-bold py-2.5 px-2 rounded-lg transition-colors {{ $isActive ? 'text-on-surface bg-surface-container' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low' }}"
               @if($isActive) aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
        <a href="https://raupulus.dev/contact" target="_blank" rel="noopener noreferrer"
           @click="mobileMenuOpen = false"
           class="block text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low font-bold py-2.5 px-2 rounded-lg transition-colors">Contacto</a>
    </nav>
</header>
