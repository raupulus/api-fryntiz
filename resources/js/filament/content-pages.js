// Pantalla de páginas de un contenido (F8 del plan de contenidos del
// 2026-09-24): autoguardado, Ctrl/Cmd+S, aviso al salir con cambios sin
// guardar, soltar el bloqueo al cerrar la pestaña y recorte de imágenes.
//
// Lo carga el render hook de `ManageContentPages`, junto a Editor.js.

import Cropper from 'cropperjs';

const LEAVE_MESSAGE = 'Tienes cambios sin guardar en esta página. ¿Salir sin guardarlos? Lo último que se autoguardó sigue en tu borrador.';

const SESSION_MESSAGE = 'La sesión ha caducado y no se ha guardado nada. Vuelve a entrar: al abrir esta página se te ofrecerá recuperar tu borrador, con lo último que se autoguardó.';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function contentPageEditor({ autosaveMs, releaseUrl, lockToken }) {
    return {
        leaving: false,
        busy: false,
        now: Date.now(),
        timers: [],
        listeners: [],

        // Distinto de lo guardado: lo que ya se autoguardó en el borrador
        // (`hasUnsavedChanges`, del servidor) o lo que aún no ha salido del
        // navegador.
        get unsaved() {
            return Boolean(this.$wire.hasUnsavedChanges) || this.$wire.$dirty('data');
        },

        get autosaveText() {
            const at = this.$wire.autosavedAt ? Date.parse(this.$wire.autosavedAt) : null;

            if (! at) {
                return '';
            }

            const seconds = Math.max(0, Math.round((this.now - at) / 1000));

            return seconds < 60
                ? `Guardado automáticamente hace ${seconds} s`
                : `Guardado automáticamente hace ${Math.round(seconds / 60)} min`;
        },

        init() {
            this.timers.push(setInterval(() => this.autosave(), autosaveMs));
            this.timers.push(setInterval(() => (this.now = Date.now()), 5000));

            this.listen(window, 'keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    this.save();
                }
            });

            // Al perder el foco (otra pestaña, otra aplicación) también.
            this.listen(window, 'blur', () => this.autosave());

            this.listen(window, 'beforeunload', (event) => {
                if (! this.leaving && this.unsaved) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });

            this.listen(window, 'pagehide', () => this.release());

            // Cualquier enlace del panel (menú, pestañas de la ficha…).
            this.listen(document, 'click', (event) => this.guardLink(event), true);

            this.$wire.$on('content-page-created', ({ url }) => window.history.replaceState(null, '', url));

            // Sesión caducada (D3): Livewire manda al login sin decir nada, y
            // lo único que se veía era el aviso genérico de salir de la página.
            this.stopIntercept = window.Livewire.interceptRequest(({ onRedirect }) => {
                onRedirect(({ url, preventDefault }) => {
                    if (! new URL(url, window.location.href).pathname.endsWith('/login')) {
                        return;
                    }

                    preventDefault();
                    this.leaving = true;
                    window.alert(SESSION_MESSAGE);
                    window.location.href = url;
                });
            });

            // Al recargar, el bloqueo de la carga anterior se suelta un
            // instante después de abrirse esta: se vuelve a probar enseguida.
            if (this.$wire.readOnly) {
                this.timers.push(setTimeout(() => this.autosave(), 3000));
            }
        },

        destroy() {
            this.stopIntercept?.();
            this.timers.forEach(clearInterval);
            this.listeners.forEach(([target, name, handler, capture]) => target.removeEventListener(name, handler, capture));
        },

        listen(target, name, handler, capture = false) {
            target.addEventListener(name, handler, capture);
            this.listeners.push([target, name, handler, capture]);
        },

        // Editor.js guarda su contenido en el estado de Livewire al cambiar,
        // pero con Ctrl/Cmd+S el foco sigue dentro: se vuelca antes.
        async flush() {
            await window.flushEditorJs?.();
        },

        async autosave() {
            if (this.busy) {
                return;
            }

            this.busy = true;

            try {
                await this.flush();
                await this.$wire.autosave();
            } finally {
                this.busy = false;
            }
        },

        async save() {
            await this.flush();
            await this.$wire.save();
        },

        // Cambiar de página guarda antes; si no se puede, no se cambia. Sin
        // cambios también se pasa por el servidor, que suelta el bloqueo: el
        // aviso de `pagehide` llega después de pintar la otra página, y su
        // lista marcaría ésta como abierta en otra pestaña.
        async go(url) {
            if (this.unsaved) {
                await this.flush();
            }

            if ((await this.$wire.saveBeforeLeaving()) !== true) {
                return;
            }

            this.leaving = true;
            window.location.href = url;
        },

        // Reordenar con el teclado (flechas sobre el asa), igual que arrastrando.
        async move(id, delta) {
            const ids = [...this.$root.querySelectorAll('.cpe-list__items [x-sortable-item]')]
                .map((item) => Number(item.getAttribute('x-sortable-item')));
            const from = ids.indexOf(id);
            const to = from + delta;

            if (from < 0 || to < 0 || to >= ids.length) {
                return;
            }

            [ids[from], ids[to]] = [ids[to], ids[from]];
            await this.$wire.reorderPages(ids);
            this.$nextTick(() => this.$root.querySelector(`[x-sortable-item="${id}"] .cpe-list__handle`)?.focus());
        },

        guardLink(event) {
            const link = event.target.closest?.('a[href]');

            if (! link || this.leaving || event.defaultPrevented || link.target === '_blank'
                || event.metaKey || event.ctrlKey || event.shiftKey || link.hasAttribute('data-page-link')
                || link.getAttribute('href').startsWith('#')) {
                return;
            }

            if (! this.unsaved) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (window.confirm(LEAVE_MESSAGE)) {
                this.leaving = true;
                window.location.href = link.href;
            }
        },

        // Al cerrar la pestaña se suelta el bloqueo: si no, los demás
        // esperarían los 2 minutos de caducidad.
        release() {
            if (! releaseUrl || this.$wire.readOnly) {
                return;
            }

            const data = new FormData();
            data.append('_token', csrfToken());
            data.append('token', lockToken);
            navigator.sendBeacon(releaseUrl, data);
        },
    };
}

// Sin girar, voltear ni mover la imagen: sólo se elige el trozo. Así las
// coordenadas del recorte son las del original.
const CROPPER_TEMPLATE = '<cropper-canvas background>'
    + '<cropper-image></cropper-image>'
    + '<cropper-shade hidden></cropper-shade>'
    + '<cropper-handle action="select" plain></cropper-handle>'
    + '<cropper-selection initial-coverage="0.8" movable resizable>'
    + '<cropper-grid role="grid" bordered covered></cropper-grid>'
    + '<cropper-handle action="move" theme-color="rgba(255, 255, 255, 0.35)"></cropper-handle>'
    + '<cropper-handle action="n-resize"></cropper-handle>'
    + '<cropper-handle action="e-resize"></cropper-handle>'
    + '<cropper-handle action="s-resize"></cropper-handle>'
    + '<cropper-handle action="w-resize"></cropper-handle>'
    + '<cropper-handle action="ne-resize"></cropper-handle>'
    + '<cropper-handle action="nw-resize"></cropper-handle>'
    + '<cropper-handle action="se-resize"></cropper-handle>'
    + '<cropper-handle action="sw-resize"></cropper-handle>'
    + '</cropper-selection>'
    + '</cropper-canvas>';

function contentImageCropper({ fileId, width, height, usages }) {
    return {
        cropper: null,
        cropping: false,

        start() {
            this.cropping = true;
            this.$nextTick(async () => {
                this.cropper = new Cropper(this.$refs.source, { template: CROPPER_TEMPLATE });

                // La selección se crea antes de que el lienzo tenga medidas y
                // nace vacía: se pone a mano, en el 80 % central de la imagen.
                await this.cropper.getCropperImage().$ready();
                await new Promise((resolve) => requestAnimationFrame(resolve));

                const canvas = this.cropper.getCropperCanvas().getBoundingClientRect();
                const image = this.cropper.getCropperImage().getBoundingClientRect();

                this.cropper.getCropperSelection().$change(
                    image.left - canvas.left + image.width * 0.1,
                    image.top - canvas.top + image.height * 0.1,
                    image.width * 0.8,
                    image.height * 0.8,
                );
            });
        },

        cancel() {
            this.cropper?.destroy();
            this.cropper = null;
            this.cropping = false;
        },

        // Coordenadas en píxeles del original, a partir de dónde están en
        // pantalla la imagen y la selección.
        area() {
            const image = this.cropper.getCropperImage().getBoundingClientRect();
            const selection = this.cropper.getCropperSelection().getBoundingClientRect();
            const scale = width / image.width;
            const clamp = (value, max) => Math.min(Math.max(Math.round(value), 0), max);

            const x = clamp((selection.left - image.left) * scale, width - 1);
            const y = clamp((selection.top - image.top) * scale, height - 1);

            return {
                x,
                y,
                width: clamp(selection.width * scale, width - x),
                height: clamp(selection.height * scale, height - y),
            };
        },

        async apply() {
            const area = this.area();

            // Un recorte cambia la imagen en todos los sitios donde se usa.
            if (usages.length > 0 && ! window.confirm(`Esta imagen también se usa en:\n\n• ${usages.join('\n• ')}\n\nEl recorte se verá en todos. ¿Recortar?`)) {
                return;
            }

            await this.$wire.cropImage(fileId, area.x, area.y, area.width, area.height);
            this.cancel();
        },
    };
}

// localStorage puede no estar (modo privado, almacenamiento bloqueado): el
// modal funciona igual, sólo que no recuerda el nivel elegido.
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

// Lo que se enseña cuando el servidor no deja ni empezar.
const AI_HTTP_MESSAGES = {
    403: 'No tienes permiso para editar este contenido.',
    419: 'La sesión ha caducado. Recarga la página y vuelve a intentarlo.',
    429: 'Has hecho muchas peticiones seguidas al asistente. Espera un minuto y vuelve a intentarlo.',
};

function contentPageAiAssistant({ streamUrl, providers, reasoningLevels, defaultReasoningLevel, pageId, isNewPage, sourceFormat }) {
    // Lo recordado sólo vale si sigue en la lista: si no, el servidor
    // rechazaría la petición sin que se viera por qué. El primer proveedor es
    // el predeterminado.
    const savedProvider = remembered('ai_provider');
    const savedLevel = remembered('ai_reasoning_level');

    return {
        mode: isNewPage ? 'create' : 'edit',
        provider: providers.some((option) => option.value === savedProvider) ? savedProvider : (providers[0]?.value ?? null),
        reasoningLevel: reasoningLevels.includes(savedLevel) ? savedLevel : defaultReasoningLevel,
        prompt: '',
        isStreaming: false,
        isThinking: false,
        isFinished: false,
        errorMessage: null,
        thoughtText: '',
        contentText: '',
        finalResult: null,
        abortController: null,

        get providerOption() {
            return providers.find((option) => option.value === this.provider) ?? null;
        },

        // «Gemini», «Mistral»… para los textos del modal.
        get providerName() {
            return this.providerOption?.name ?? 'la IA';
        },

        // Si el proveedor elegido admite nivel de razonamiento.
        get hasReasoningLevels() {
            return this.providerOption?.reasoning === true;
        },

        // Lo que escribe la IA, con las marcas de los bloques que no ve
        // (`[[BLOQUE_1]]`) dichas en claro: al aplicar, en cada una vuelve su
        // bloque original.
        get displayText() {
            return this.contentText.replace(/\\?\[\\?\[BLOQUE_(\d+)\\?\]\\?\]/g, '[bloque $1: se conserva tal cual]');
        },

        init() {
            // Cerrar el modal mientras escribe para la petición: si no, seguía
            // en segundo plano gastando tokens. `isOpen` es del modal de
            // Filament que lo contiene.
            this.$watch('isOpen', (open) => {
                if (open === false) {
                    this.cancel();
                }
            });
        },

        destroy() {
            this.cancel();
        },

        setSuggestion(text) {
            this.prompt = text;
        },

        async startStream() {
            if (this.isStreaming) return;

            remember('ai_provider', this.provider);
            remember('ai_reasoning_level', this.reasoningLevel);

            await window.flushEditorJs?.();

            this.isStreaming = true;
            this.isThinking = false;
            this.isFinished = false;
            this.errorMessage = null;
            this.thoughtText = '';
            this.contentText = '';
            this.finalResult = null;
            this.abortController = new AbortController();

            const currentData = this.$wire.data || {};
            const format = currentData.source_format || sourceFormat;
            const field = { editorjs: 'content_json', markdown: 'content_markdown' }[format] ?? 'content_html';

            const payload = {
                page_id: pageId,
                mode: this.mode,
                provider: this.provider,
                reasoning_level: this.hasReasoningLevels ? this.reasoningLevel : null,
                prompt: this.prompt,
                current_content: currentData[field] || '',
                source_format: format,
            };

            try {
                const response = await fetch(streamUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        // JSON primero: así los errores (validación, límite)
                        // llegan como JSON y no como una página HTML.
                        'Accept': 'application/json, text/event-stream',
                    },
                    body: JSON.stringify(payload),
                    signal: this.abortController.signal,
                });

                if (! response.ok) {
                    let message = AI_HTTP_MESSAGES[response.status] ?? null;

                    if (! message) {
                        try {
                            message = (await response.json()).message;
                        } catch (_) {
                            // Sin cuerpo legible.
                        }
                    }

                    this.errorMessage = message || `El asistente no ha podido empezar (error ${response.status}).`;
                    this.isStreaming = false;

                    return;
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const blocks = buffer.split('\n\n');
                    buffer = blocks.pop() || '';

                    for (const block of blocks) {
                        this.processEventBlock(block);
                    }
                }

                if (buffer.trim()) {
                    this.processEventBlock(buffer);
                }

                // Sin `complete` ni `error`: la conexión se cortó a medias.
                if (! this.finalResult && ! this.errorMessage) {
                    this.errorMessage = `La conexión con ${this.providerName} se ha cortado antes de terminar. Vuelve a intentarlo.`;
                }

                this.isStreaming = false;
                this.isThinking = false;
                this.isFinished = true;
            } catch (err) {
                this.errorMessage = err.name === 'AbortError'
                    ? 'Generación detenida.'
                    : `Error de conexión: ${err.message}`;
                this.isStreaming = false;
                this.isThinking = false;
            }
        },

        processEventBlock(block) {
            let event = 'message';
            let dataStr = '';

            for (const line of block.split('\n')) {
                if (line.startsWith('event: ')) {
                    event = line.slice(7).trim();
                } else if (line.startsWith('data: ')) {
                    dataStr = line.slice(6).trim();
                }
            }

            if (! dataStr) return;

            let data;
            try {
                data = JSON.parse(dataStr);
            } catch (_) {
                return;
            }

            if (event === 'thought') {
                this.isThinking = true;
                this.thoughtText += data.text || '';
            } else if (event === 'content') {
                this.isThinking = false;
                this.contentText += data.text || '';
            } else if (event === 'complete') {
                this.finalResult = data;
            } else if (event === 'error') {
                this.errorMessage = data.message || 'Error desconocido';
                this.isStreaming = false;
                this.isThinking = false;
            } else if (event === 'done') {
                this.isStreaming = false;
                this.isThinking = false;
                this.isFinished = true;
            }
        },

        cancel() {
            if (this.isStreaming) {
                this.abortController?.abort();
            }
            this.isStreaming = false;
            this.isThinking = false;
        },

        async apply(append = false) {
            if (! this.finalResult) return;

            if (this.mode === 'create') {
                await this.$wire.applyAiNewPage(
                    this.finalResult.title || 'Nueva página',
                    this.finalResult.slug || 'nueva-pagina',
                    this.finalResult.content || this.contentText,
                );
            } else {
                await this.$wire.applyAiContent(
                    this.finalResult.content || this.contentText,
                    append,
                );
            }
        },
    };
}

const register = () => {
    window.Alpine.data('contentPageEditor', contentPageEditor);
    window.Alpine.data('contentImageCropper', contentImageCropper);
    window.Alpine.data('contentPageAiAssistant', contentPageAiAssistant);
};

if (window.Alpine) {
    register();
} else {
    document.addEventListener('alpine:init', register);
}
