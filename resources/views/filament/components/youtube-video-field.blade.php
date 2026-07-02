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
            platformStatePath: @js($getPlatformStatePath()),
            uid: @js($uid),
        })"
        x-init="init()"
        class="space-y-3"
    >
        <div class="flex items-center gap-3 flex-wrap">
            <button
                type="button"
                id="btn-{{ $uid }}"
                class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-primary-500"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor" class="w-4 h-4">
                    <path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6C14.9 167 14.9 256.4 14.9 256.4s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.1 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zM232 337.6V175.2l142.7 81.2L232 337.6z"/>
                </svg>
                Buscar vídeo en YouTube
            </button>

            <input
                type="text"
                x-model="state"
                placeholder="ID del vídeo de YouTube"
                class="fi-input block w-56 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
            />
        </div>

        {{-- Contenedor del modal (el plugin JS inyecta el DOM aquí) --}}
        <div id="modal-{{ $uid }}"></div>

        {{-- Vista previa --}}
        <div x-show="state" class="mt-2">
            <iframe
                :src="state ? ('https://www.youtube.com/embed/' + state) : ''"
                class="w-full max-w-xl aspect-video rounded-lg border border-gray-200 dark:border-gray-700"
                frameborder="0"
                allowfullscreen
            ></iframe>
        </div>
    </div>
</x-dynamic-component>

@once
    @push('scripts')
        <link href="{{ asset('css/youtube_video_search.css') }}" rel="stylesheet" />
        <script src="{{ asset('js/youtube_video_search.js') }}"></script>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('youtubeVideoField', ({ state, apiKey, channels, platformStatePath, uid }) => ({
                    state: state,
                    uid: uid,
                    searcher: null,

                    init() {
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

                        this.searcher = new YoutubeVideoSearch(
                            apiKey,
                            channelId,
                            '#modal-' + uid,
                            callback,
                            '#btn-' + uid,
                        );

                        // Al abrir el modal, refrescar el canal según la plataforma seleccionada.
                        const btn = document.getElementById('btn-' + uid);
                        if (btn) {
                            btn.addEventListener('click', () => {
                                const ch = this.resolveChannelId();
                                if (ch) {
                                    this.searcher.setChannelId = ch;
                                }
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
                }));
            });
        </script>
    @endpush
@endonce
