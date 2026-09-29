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

const register = () => {
    window.Alpine.data('contentPageEditor', contentPageEditor);
    window.Alpine.data('contentImageCropper', contentImageCropper);
};

if (window.Alpine) {
    register();
} else {
    document.addEventListener('alpine:init', register);
}
