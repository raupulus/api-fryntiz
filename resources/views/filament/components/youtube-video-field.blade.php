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
        class="field-wrapper-youtube-video-search"
    >
        {{--
            Layout con CSS propio (field-layout-youtube-video-search y
            compañía, en youtube-video-search-tailwind.css), no con
            utilidades de Tailwind (`grid grid-cols-5`, `md:col-span-2`...).
            Verificado que el navegador real con el que se probó no las
            aplicaba —el contenedor calculaba `display: block` en vez de
            `flex`—, así que se escribe con flexbox y `@media (min-width)`
            clásicos, soportados en cualquier navegador desde hace años.
        --}}
        <div class="field-layout-youtube-video-search">
            <div class="field-controls-youtube-video-search">
                <button
                    type="button"
                    id="btn-{{ $uid }}"
                    class="field-btn-youtube-video-search field-btn-primary-youtube-video-search"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor">
                        <path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6C14.9 167 14.9 256.4 14.9 256.4s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.1 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zM232 337.6V175.2l142.7 81.2L232 337.6z"/>
                    </svg>
                    Buscar vídeo en YouTube
                </button>

                <button
                    type="button"
                    x-show="state"
                    @click="confirmRemoveOpen = true"
                    class="field-btn-youtube-video-search field-btn-danger-youtube-video-search"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor">
                        <path d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0H284.2c12.1 0 23.2 6.8 28.6 17.7L320 32h96c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 96 0 81.7 0 64S14.3 32 32 32h96l7.2-14.3zM32 128H416L394.8 467c-1.6 25.3-22.6 45-47.9 45H101.1c-25.3 0-46.3-19.7-47.9-45L32 128z"/>
                    </svg>
                    Quitar vídeo asociado
                </button>

                <input
                    type="text"
                    x-model="state"
                    placeholder="ID del vídeo de YouTube"
                    class="field-input-youtube-video-search"
                />
            </div>

            {{--
                Vista previa: a la derecha y más grande que los controles.

                Miniatura estática + play en vez de cargar el iframe de
                golpe: recién elegido el vídeo se veía nítido, pero al
                recargar la página el embed arrancaba a intentar reproducir
                (autoplay del navegador) a la calidad más baja mientras
                cargaba, estirada a toda la caja — de ahí el pixelado. La
                miniatura de i.ytimg.com es una imagen fija y siempre a la
                misma resolución; el iframe real sólo se monta al pulsar.
            --}}
            <div x-show="state" class="field-preview-youtube-video-search">
                <div
                    x-show="!videoPlaying"
                    @click="videoPlaying = true"
                    class="field-preview-thumb-youtube-video-search"
                >
                    <img
                        :src="state ? ('https://i.ytimg.com/vi/' + state + '/hqdefault.jpg') : ''"
                        alt="Miniatura del vídeo"
                        class="field-preview-thumb-img-youtube-video-search"
                    />
                    <span class="field-preview-play-youtube-video-search">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor">
                            <path d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.2 40.6 61.9 72.4 43.3l352-208.1c31.4-18.5 31.5-64.4 0-83.5z"/>
                        </svg>
                    </span>
                </div>

                <iframe
                    x-show="videoPlaying"
                    :src="videoPlaying && state ? ('https://www.youtube.com/embed/' + state + '?autoplay=1') : ''"
                    class="field-preview-iframe-youtube-video-search"
                    frameborder="0"
                    allow="autoplay; encrypted-media; picture-in-picture"
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
            class="confirm-dialog-youtube-video-search"
            @keydown.escape.window="confirmRemoveOpen = false"
            @click="confirmRemoveOpen = false"
        >
            <div
                class="confirm-dialog-box-youtube-video-search"
                @click.stop
            >
                <span class="confirm-dialog-title-youtube-video-search">Quitar vídeo asociado</span>
                <span class="confirm-dialog-text-youtube-video-search">
                    Se quitará el vídeo de YouTube asociado a este contenido. Podrás buscar y asociar otro después. Los cambios se guardan al guardar el formulario.
                </span>
                <div class="confirm-dialog-actions-youtube-video-search">
                    <button
                        type="button"
                        @click="confirmRemoveOpen = false"
                        class="field-btn-youtube-video-search field-btn-secondary-youtube-video-search"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="state = ''; confirmRemoveOpen = false;"
                        class="field-btn-youtube-video-search field-btn-danger-youtube-video-search"
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
                    videoPlaying: false,

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
                                this.videoPlaying = false;
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
