<header class="fixed top-0 w-full z-50 glass-nav shadow-sm" x-data="{ mobileMenuOpen: false }">
    <div class="flex justify-between items-center px-6 py-4 max-w-7xl mx-auto w-full">
        {{-- Logo + Título: en desktop se muestra logo + texto, en mobile solo logo --}}
        <a class="flex items-center gap-3" href="{{ route('home') }}">
            <img src="{{ asset('images/logo/logo128x128.webp') }}"
                 alt="Logo Api Raupulus"
                 class="w-10 h-10" />
            <span class="text-xl font-bold tracking-tighter text-on-surface hidden sm:inline">
                Api Raupulus
            </span>
        </a>

        {{-- Navegación desktop — visible desde md en adelante --}}
        <nav class="hidden md:flex items-center space-x-8">
            <a class="text-on-surface-variant hover:text-on-surface transition-colors font-body text-sm font-bold tracking-tight"
               href="{{ route('weather_station.index') }}">Weather Station</a>
            <a class="text-on-surface-variant hover:text-on-surface transition-colors font-body text-sm font-bold tracking-tight"
               href="{{ route('keycounter.index') }}">Keycounter</a>
            <a class="text-on-surface-variant hover:text-on-surface transition-colors font-body text-sm font-bold tracking-tight"
               href="{{ route('airflight.index') }}">Airflight</a>
            <a class="text-on-surface-variant hover:text-on-surface transition-colors font-body text-sm font-bold tracking-tight"
               href="{{ route('smartplant.index') }}">Smart Plant</a>
            <a class="text-on-surface-variant hover:text-on-surface transition-colors font-body text-sm font-bold tracking-tight"
               href="{{ route('hardware.energy.index') }}">Energy</a>
            <a class="text-on-surface-variant hover:text-on-surface transition-colors font-body text-sm font-bold tracking-tight"
               href="https://raupulus.dev/contact" target="_blank">Contacto</a>
        </nav>

        {{-- Controles: selector tema + hamburguesa (solo mobile) --}}
        <div class="flex items-center gap-2">
            {{-- Selector de tema (siempre visible) --}}
            <button @click="darkMode = !darkMode"
                    class="p-2 rounded-lg hover:bg-surface-container transition-colors"
                    :title="darkMode ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'">
                <span x-show="!darkMode" class="material-symbols-outlined text-on-surface-variant">dark_mode</span>
                <span x-show="darkMode" class="material-symbols-outlined text-on-surface-variant">light_mode</span>
            </button>

            {{-- Hamburguesa SOLO en mobile (oculto en md+) --}}
            <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden p-2 rounded-lg hover:bg-surface-container transition-colors"
                    title="Menú">
                <span class="material-symbols-outlined text-on-surface-variant">menu</span>
            </button>
        </div>
    </div>

    {{-- Menú mobile desplegable (oculto en md+) --}}
    <div x-show="mobileMenuOpen"
         x-transition
         class="md:hidden bg-surface border-t border-outline-variant/15 px-6 py-4 space-y-3">
        <a href="{{ route('weather_station.index') }}" class="block text-on-surface-variant hover:text-on-surface font-bold py-1">Weather Station</a>
        <a href="{{ route('keycounter.index') }}" class="block text-on-surface-variant hover:text-on-surface font-bold py-1">Keycounter</a>
        <a href="{{ route('airflight.index') }}" class="block text-on-surface-variant hover:text-on-surface font-bold py-1">Airflight</a>
        <a href="{{ route('smartplant.index') }}" class="block text-on-surface-variant hover:text-on-surface font-bold py-1">Smart Plant</a>
        <a href="{{ route('hardware.energy.index') }}" class="block text-on-surface-variant hover:text-on-surface font-bold py-1">Energy</a>
        <a href="https://raupulus.dev/contact" target="_blank" class="block text-on-surface-variant hover:text-on-surface font-bold py-1">Contacto</a>
    </div>
</header>
