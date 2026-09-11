<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        $uid = 'yt-' . \Illuminate\Support\Str::slug($getStatePath(), '-');
    @endphp

    <div
        wire:ignore
        x-data="youtubeVideoField({
            state: $wire.$entangle('{{ $getStatePath() }}'),
            apiKey: @js($getApiKey()),
            channels: @js($getChannels()),
            platformNames: @js($getPlatformNames()),
            platformStatePath: @js($getPlatformStatePath()),
            uid: @js($uid),
        })"
        x-init="init()"
        class="space-y-3"
    >
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-start">
            <div class="md:col-span-2 flex flex-col gap-2">
                <button
                    type="button"
                    id="btn-{{ $uid }}"
                    class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-primary-500"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor" class="w-4 h-4">
                        <path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6C14.9 167 14.9 256.4 14.9 256.4s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.1 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zM232 337.6V175.2l142.7 81.2L232 337.6z"/>
                    </svg>
                    Buscar vídeo en YouTube
                </button>

                <button
                    type="button"
                    x-show="state"
                    x-cloak
                    @click="confirmRemoveOpen = true"
                    class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-red-500"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" class="w-4 h-4">
                        <path d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0H284.2c12.1 0 23.2 6.8 28.6 17.7L320 32h96c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 96 0 81.7 0 64S14.3 32 32 32h96l7.2-14.3zM32 128H416L394.8 467c-1.6 25.3-22.6 45-47.9 45H101.1c-25.3 0-46.3-19.7-47.9-45L32 128z"/>
                    </svg>
                    Quitar vídeo asociado
                </button>

                <input
                    type="text"
                    x-model="state"
                    placeholder="ID del vídeo de YouTube"
                    class="fi-input block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                />
            </div>

            {{-- Vista previa: a la derecha y más grande que los controles. --}}
            <div x-show="state" x-cloak class="md:col-span-3">
                <iframe
                    :src="state ? ('https://www.youtube.com/embed/' + state) : ''"
                    class="w-full aspect-video rounded-lg border border-gray-200 dark:border-gray-700"
                    frameborder="0"
                    allowfullscreen
                ></iframe>
            </div>
        </div>

        {{--
            Contenedor del modal de búsqueda (el plugin JS inyecta el DOM
            aquí). Arranca oculto con esta clase: en `main` el propio HTML ya
            la llevaba de partida y `closeModal()` sólo la restituye tras
            abrir. Sin ella el modal queda visible en cuanto se instancia
            `YoutubeVideoSearch` —al montar el campo—, y como en Filament esta
            pestaña estaba oculta hasta seleccionarla, el efecto era «se abre
            solo el buscador al entrar en Vídeo y enlaces».
        --}}
        <div id="modal-{{ $uid }}" class="modal-youtube-video-search-hidden"></div>

        {{-- Confirmación para quitar el vídeo asociado. --}}
        <div
            x-show="confirmRemoveOpen"
            x-cloak
            class="fixed inset-0 z-[1050] flex items-center justify-center bg-black/60 p-4"
            @keydown.escape.window="confirmRemoveOpen = false"
            @click="confirmRemoveOpen = false"
        >
            <div
                class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl p-6"
                @click.stop
            >
                <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-2">Quitar vídeo asociado</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Se quitará el vídeo de YouTube asociado a este contenido. Podrás buscar y asociar otro después. Los cambios se guardan al guardar el formulario.
                </p>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="confirmRemoveOpen = false"
                        class="fi-btn fi-btn-size-md rounded-lg bg-gray-100 dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="state = ''; confirmRemoveOpen = false;"
                        class="fi-btn fi-btn-size-md rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500"
                    >
                        Quitar vídeo
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

@once
    @push('scripts')
        {{--
            Va por Vite, no por `asset()`. Con `asset()` apuntaba a
            `public/js` y `public/css`, dos directorios que `.gitignore`
            excluye: el fichero no llegaba al servidor y el 404 en HTML se
            veía en consola como una discordancia de tipo MIME.
        --}}
        @vite('resources/js/youtube-video-search.js')

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('youtubeVideoField', ({ state, apiKey, channels, platformNames, platformStatePath, uid }) => ({
                    state: state,
                    uid: uid,
                    searcher: null,
                    confirmRemoveOpen: false,

                    init() {
                        // Livewire puede volver a montar este elemento (wire:ignore
                        // protege sus hijos de un morph, pero no evita que Alpine
                        // vuelva a llamar a x-init sobre el mismo nodo en algunos
                        // ciclos de hidratación). Sin este guard, una segunda
                        // llamada crea un segundo modal dentro del mismo
                        // contenedor y, como `YoutubeVideoSearch` resolvía su
                        // input con un querySelector por id global, la instancia
                        // nueva (la que se ve) terminaba enganchando sus eventos
                        // al input de la primera (invisible debajo): se podía
                        // escribir sin que nada reaccionara.
                        if (this.$el.dataset.ytInitialized) {
                            return;
                        }
                        this.$el.dataset.ytInitialized = 'true';

                        if (typeof YoutubeVideoSearch === 'undefined') {
                            console.error('YoutubeVideoSearch no está cargado.');
                            return;
                        }

                        const channelId = this.resolveChannelId();

                        const callback = (e, video) => {
                            if (video && video.id) {
                                this.state = video.id;
                            }
                            this.searcher.closeModal();
                        };

                        // Resueltos dentro de `this.$el`, no por id global: si el
                        // campo llegara a existir dos veces en la página (ids
                        // duplicados), cada instancia sigue apuntando a su propio
                        // botón y a su propio contenedor.
                        const btn = this.$el.querySelector('#btn-' + uid);
                        const modalContainer = this.$el.querySelector('#modal-' + uid);

                        this.searcher = new YoutubeVideoSearch(
                            apiKey,
                            channelId,
                            modalContainer,
                            callback,
                            btn,
                        );

                        this.searcher.setChannelBadge(this.resolvePlatformName(), channelId);

                        // Al abrir el modal, refrescar el canal según la plataforma seleccionada.
                        if (btn) {
                            btn.addEventListener('click', () => {
                                const ch = this.resolveChannelId();
                                if (ch) {
                                    this.searcher.setChannelId = ch;
                                }
                                this.searcher.setChannelBadge(this.resolvePlatformName(), ch);
                            });
                        }
                    },

                    resolveChannelId() {
                        try {
                            const platformId = this.$wire.get(platformStatePath);
                            if (platformId && channels[platformId]) {
                                return channels[platformId];
                            }
                        } catch (e) {
                            // Sin plataforma seleccionada todavía.
                        }

                        // Fallback: primer canal disponible del mapa.
                        const values = Object.values(channels).filter(Boolean);
                        return values.length ? values[0] : null;
                    },

                    resolvePlatformName() {
                        try {
                            const platformId = this.$wire.get(platformStatePath);
                            if (platformId && platformNames[platformId]) {
                                return platformNames[platformId];
                            }
                        } catch (e) {
                            // Sin plataforma seleccionada todavía.
                        }

                        return null;
                    },
                }));
            });
        </script>
    @endpush
@endonce
