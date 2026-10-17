// Portadas con IA (la de la entrada y la de cada página): el modal que pide
// de 1 a 5 imágenes, las enseña y pone la elegida en el campo de portada.
//
// Lo cargan los render hooks de `EditContent` y `ManageContentPages`. El
// servidor responde en SSE (ver `ContentAiImageController`): `image` por cada
// una (con la dirección de su vista previa), `done` al final o sólo `error`;
// los `: ping` mientras espera no llevan datos y se ignoran.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

// localStorage puede no estar (modo privado, almacenamiento bloqueado): el
// modal funciona igual, sólo que no recuerda lo elegido.
function remembered(key) {
    try {
        return window.localStorage.getItem(key);
    } catch (_) {
        return null;
    }
}

function remember(key, value) {
    try {
        window.localStorage.setItem(key, value);
    } catch (_) {
        // Sin almacenamiento, no se recuerda.
    }
}

// Espera a que se cumpla algo, como mucho `ms`. Devuelve si se ha cumplido.
async function waitFor(check, ms) {
    const until = Date.now() + ms;

    while (Date.now() < until) {
        if (check()) {
            return true;
        }

        await new Promise((resolve) => setTimeout(resolve, 100));
    }

    return check();
}

const HTTP_MESSAGES = {
    403: 'No tienes permiso para editar este contenido.',
    419: 'La sesión ha caducado. Recarga la página y vuelve a intentarlo.',
    429: 'Has hecho muchas peticiones seguidas a la IA. Espera un minuto y vuelve a intentarlo.',
};

function contentAiImages({ streamUrl, providers, mode, source, pageId }) {
    const saved = remembered('ai_image_provider');
    const initialProvider = providers.some((p) => p.value === saved) ? saved : (providers[0]?.value ?? '');

    const qualityFor = (value) => {
        const option = providers.find((p) => p.value === value);
        const stored = remembered(`ai_image_quality:${value}`);

        return option?.qualities.some((q) => q.value === stored) ? stored : (option?.defaultQuality ?? null);
    };

    const savedCount = Number.parseInt(remembered('ai_image_count') ?? '', 10);

    return {
        provider: initialProvider,
        quality: qualityFor(initialProvider),
        count: savedCount >= 1 && savedCount <= 5 ? savedCount : 2,
        useColors: remembered('ai_image_colors') !== '0',
        prompt: '',
        isWorking: false,
        startedAt: null,
        elapsed: 0,
        errorMessage: null,
        images: [],
        choosing: null,
        abortController: null,
        timer: null,

        get providerOption() {
            return providers.find((p) => p.value === this.provider) ?? null;
        },

        get providerName() {
            return this.providerOption?.name ?? 'la IA';
        },

        get qualities() {
            return this.providerOption?.qualities ?? [];
        },

        // Gemini y Mistral dan una por petición: más imágenes, más peticiones
        // a la vez (y más gasto).
        get waitText() {
            const seconds = this.elapsed;

            return `${this.providerName} está ${mode === 'improve' ? 'mejorando la imagen' : 'dibujando'}… ${seconds} s`;
        },

        init() {
            this.$watch('provider', (value) => {
                this.quality = qualityFor(value);
            });

            // Cerrar el modal mientras genera para la petición.
            this.$watch('isOpen', (open) => {
                if (open === false) {
                    this.cancel();
                }
            });
        },

        destroy() {
            this.cancel();
        },

        async start() {
            if (this.isWorking || ! this.provider) return;

            remember('ai_image_provider', this.provider);
            remember('ai_image_count', String(this.count));
            remember('ai_image_colors', this.useColors ? '1' : '0');

            if (this.quality) {
                remember(`ai_image_quality:${this.provider}`, this.quality);
            }

            this.isWorking = true;
            this.errorMessage = null;
            this.images = [];
            this.startedAt = Date.now();
            this.elapsed = 0;
            this.timer = setInterval(() => (this.elapsed = Math.round((Date.now() - this.startedAt) / 1000)), 1000);
            this.abortController = new AbortController();

            try {
                const response = await fetch(streamUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json, text/event-stream',
                    },
                    body: JSON.stringify({
                        mode,
                        provider: this.provider,
                        count: Number(this.count),
                        quality: this.quality,
                        use_colors: this.useColors,
                        page_id: pageId,
                        prompt: this.prompt,
                        source,
                    }),
                    signal: this.abortController.signal,
                });

                if (! response.ok) {
                    let message = HTTP_MESSAGES[response.status] ?? null;

                    if (! message) {
                        try {
                            message = (await response.json()).message;
                        } catch (_) {
                            // Sin cuerpo legible.
                        }
                    }

                    this.errorMessage = message || `No se ha podido empezar (error ${response.status}).`;

                    return;
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let buffer = '';
                let finished = false;

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const blocks = buffer.split('\n\n');
                    buffer = blocks.pop() || '';

                    for (const block of blocks) {
                        finished = this.handle(block) || finished;
                    }
                }

                if (buffer.trim()) {
                    finished = this.handle(buffer) || finished;
                }

                if (! finished && ! this.errorMessage) {
                    this.errorMessage = `La conexión con ${this.providerName} se ha cortado antes de terminar. Vuelve a intentarlo.`;
                }
            } catch (err) {
                this.errorMessage = err.name === 'AbortError' ? 'Detenido.' : `Error de conexión: ${err.message}`;
            } finally {
                this.isWorking = false;
                clearInterval(this.timer);
            }
        },

        // Devuelve si ha terminado (`done` o `error`).
        handle(block) {
            let event = 'message';
            let dataStr = '';

            for (const line of block.split('\n')) {
                if (line.startsWith('event: ')) {
                    event = line.slice(7).trim();
                } else if (line.startsWith('data: ')) {
                    dataStr = line.slice(6).trim();
                }
            }

            if (! dataStr) return false;

            let data;
            try {
                data = JSON.parse(dataStr);
            } catch (_) {
                return false;
            }

            if (event === 'image') {
                this.images.push(data);
            } else if (event === 'error') {
                this.errorMessage = data.message || 'Error desconocido';

                return true;
            } else if (event === 'done') {
                return true;
            }

            return false;
        },

        cancel() {
            this.abortController?.abort();
            clearInterval(this.timer);
        },

        // La elegida entra en el campo de portada como si se subiera a mano:
        // el componente de subida de Filament no sabe enseñar una subida que
        // ponga el servidor, y así además pasa por el mismo recorte a 1600 ×
        // 900 y se guarda igual que cualquier otra (D53).
        //
        // Con orden: si el campo vuelve a cargar sus ficheros mientras tanto
        // (lo hace al ver el estado vacío tras quitar la anterior), se lleva
        // por delante la nueva. Por eso no se le deja hasta que la nueva está
        // subida.
        async choose(image) {
            this.choosing = image.url;
            this.errorMessage = null;

            const field = document.querySelector('[data-ai-cover-field]');
            const upload = field ? window.Alpine.$data(field) : null;
            const pond = upload?.pond;

            try {
                if (! pond) {
                    throw new Error('No se encuentra el campo de la portada. Recarga la página.');
                }

                const response = await fetch(image.url, { credentials: 'same-origin' });

                if (! response.ok) {
                    throw new Error('La imagen ha caducado. Vuelve a generarla.');
                }

                const blob = await response.blob();
                const type = blob.type || 'image/jpeg';
                const file = new File([blob], `portada-ia.${type.split('/')[1] ?? 'jpg'}`, { type });

                await this.$wire.rememberAiCover();

                upload.shouldUpdateState = false;

                // Sustituye a la que hubiera: el campo es de una sola imagen.
                if (pond.getFiles().length > 0) {
                    pond.removeFiles();
                    await waitFor(() => Object.keys(upload.state ?? {}).length === 0, 10000);
                }

                await pond.addFile(file);

                if (! await waitFor(() => Object.values(upload.state ?? {}).some((value) => String(value).startsWith('livewire-file:')), 60000)) {
                    throw new Error('No se ha podido poner la imagen en el campo. Vuelve a intentarlo.');
                }

                upload.lastState = JSON.stringify(upload.state);
                await this.$wire.unmountAction();
            } catch (err) {
                this.errorMessage = err.message;
            } finally {
                if (upload) {
                    upload.shouldUpdateState = true;
                }

                this.choosing = null;
            }
        },
    };
}

const register = () => {
    window.Alpine.data('contentAiImages', contentAiImages);
};

if (window.Alpine) {
    register();
} else {
    document.addEventListener('alpine:init', register);
}
