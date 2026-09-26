// Editor.js del panel: herramientas y componente Alpine `editorJsField`.
//
// Antes eran ficheros sueltos en `public/vendor/editorjs` (Editor.js 2.29 y
// herramientas de versiones mezcladas) y el componente vivía en línea en
// `editorjs-scripts.blade.php`. Ahora las versiones están fijadas en
// `package.json` y Vite empaqueta todo en esta entrada, que carga el render
// hook del panel (ver `AdminPanelProvider` y `editorjs-scripts.blade.php`).
//
// El campo (`editorjs-field.blade.php`) sólo monta el componente, con las
// rutas de subida y de metadatos de su contenido (`EditorJsField::getEndpoints()`).

import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import Paragraph from '@editorjs/paragraph';
import List from '@editorjs/list';
import Checklist from '@editorjs/checklist';
import Quote from '@editorjs/quote';
import Delimiter from '@editorjs/delimiter';
import Table from '@editorjs/table';
import Embed from '@editorjs/embed';
import RawTool from '@editorjs/raw';
import Warning from '@editorjs/warning';
import Marker from '@editorjs/marker';
import InlineCode from '@editorjs/inline-code';
import AttachesTool from '@editorjs/attaches';
import LinkTool from '@editorjs/link';
import ImageTool from '@editorjs/image';
import TextVariantTune from '@editorjs/text-variant-tune';
import Alert from 'editorjs-alert';
import CodeBlock, { codeLanguages } from './editorjs-code.js';
import { hardcoded, messages } from './editorjs-i18n.js';

/**
 * Herramientas del editor.
 *
 * Los nombres (`header`, `list`, `linkTool`…) son el `type` que se guarda en
 * cada bloque: el HTML que se sirve (`TextFormatParseHelper`) y los datos que
 * ya hay dependen de ellos, así que no se cambian.
 */
function buildTools(endpoints, allowRaw, notify) {
    const uploader = createUploader(endpoints, notify);

    return {
        paragraph: { class: Paragraph, inlineToolbar: true },

        // El h1 y el h2 los pone la web: título del contenido y de la página.
        header: {
            class: Header,
            config: { levels: [3, 4, 5, 6], defaultLevel: 3, placeholder: 'Título' },
        },

        // La herramienta de listas 2.x trae también listas de casillas; en el
        // menú «+» se deja sólo la de viñetas y la numerada porque las de
        // casillas tienen su propia herramienta (`checklist`). Desde las
        // opciones de una lista se puede pasar igualmente a casillas.
        list: {
            class: List,
            inlineToolbar: true,
            toolbox: [{ title: 'Unordered List' }, { title: 'Ordered List' }],
            config: { defaultStyle: 'unordered' },
        },
        checklist: { class: Checklist, inlineToolbar: true },

        quote: {
            class: Quote,
            inlineToolbar: true,
            shortcut: 'CMD+SHIFT+O',
            config: {
                quotePlaceholder: 'Texto de la cita',
                captionPlaceholder: 'Autor de la cita',
            },
        },
        warning: {
            class: Warning,
            inlineToolbar: true,
            shortcut: 'CMD+SHIFT+W',
            config: {
                titlePlaceholder: 'Título del aviso',
                messagePlaceholder: 'Mensaje',
            },
        },
        alert: {
            class: Alert,
            inlineToolbar: true,
            config: { messagePlaceholder: 'Texto de la alerta' },
        },
        delimiter: Delimiter,
        table: { class: Table, inlineToolbar: true, config: { rows: 3, cols: 3 } },
        code: {
            class: CodeBlock,
            config: { languages: codeLanguages },
        },
        // HTML libre: sólo administradores (el servidor lo comprueba también).
        // Sin la herramienta, un bloque de HTML que ya existía se ve como «no
        // se puede mostrar» y se guarda tal cual.
        ...(allowRaw ? { raw: { class: RawTool, config: { placeholder: 'Código HTML' } } } : {}),

        // Vídeos y demás: se crean al pegar la dirección en un párrafo.
        embed: { class: Embed, inlineToolbar: true },

        // Imágenes, adjuntos y tarjetas de enlace necesitan las rutas del
        // contenido; sin ellas no se ofrecen.
        ...(endpoints.upload ? {
            image: {
                class: ImageTool,
                config: {
                    types: 'image/*',
                    uploader,
                    captionPlaceholder: 'Pie de foto',
                },
            },
            attaches: {
                class: AttachesTool,
                config: {
                    uploader,
                    buttonText: 'Elegir fichero',
                    errorMessage: 'No se ha subido el fichero',
                },
            },
            linkTool: {
                class: LinkTool,
                config: { endpoint: endpoints.urlMetadata },
            },
        } : {}),

        marker: { class: Marker, shortcut: 'CMD+SHIFT+M' },
        inlineCode: { class: InlineCode, shortcut: 'CMD+SHIFT+C' },

        textVariant: TextVariantTune,
    };
}

/**
 * Subidas de la imagen y del adjunto.
 *
 * Las herramientas traen su propio envío, pero ante un error sólo enseñan
 * «no se ha podido subir». El servidor dice el motivo (`{success: 0,
 * message}`: pesa demasiado, es un HEIC que no se puede leer, la URL no es una
 * imagen…) y aquí se enseña.
 */
function createUploader(endpoints, notify) {
    const send = async (url, init) => {
        let response;

        try {
            response = await fetch(url, {
                ...init,
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': endpoints.csrf, Accept: 'application/json', ...(init.headers ?? {}) },
            });
        } catch (e) {
            notify('No se ha podido conectar con el servidor.');
            throw e;
        }

        let body = {};

        try {
            body = await response.json();
        } catch (e) {
            // Un error de nginx o de PHP (413, 500) no trae JSON.
        }

        if (! response.ok || ! body.success) {
            const message = body.message
                ?? { 413: 'El fichero pesa más de lo que admite el servidor.', 419: 'La sesión ha caducado: recarga la página.', 429: 'Demasiadas subidas seguidas: espera un minuto.' }[response.status]
                ?? 'No se ha podido subir el fichero.';

            notify(message);
            throw new Error(message);
        }

        return body;
    };

    return {
        uploadByFile(file) {
            const data = new FormData();
            data.append('file', file);

            return send(endpoints.upload, { method: 'POST', body: data });
        },
        uploadByUrl(url) {
            return send(endpoints.byUrl, {
                method: 'POST',
                body: JSON.stringify({ url }),
                headers: { 'Content-Type': 'application/json' },
            });
        },
    };
}

/**
 * Cambia en el DOM los textos que alguna herramienta escribe sin pasar por
 * `i18n` (ver `hardcoded` en `editorjs-i18n.js`).
 */
function translateHardcoded(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);

    for (let node = walker.nextNode(); node; node = walker.nextNode()) {
        const text = node.nodeValue.trim();

        // Lo que se puede editar es texto de quien escribe, no de la interfaz.
        if (node.parentElement?.isContentEditable) {
            continue;
        }

        if (hardcoded.text[text] !== undefined) {
            node.nodeValue = node.nodeValue.replace(text, hardcoded.text[text]);
        }
    }

    root.querySelectorAll?.('[data-placeholder]').forEach((element) => {
        const placeholder = element.getAttribute('data-placeholder');

        if (hardcoded.placeholder[placeholder] !== undefined) {
            element.setAttribute('data-placeholder', hardcoded.placeholder[placeholder]);
        }
    });
}

/**
 * Quita del contenido las marcas que Editor.js pone en el DOM mientras se
 * edita.
 *
 * Desde la 2.30, Editor.js marca con `data-empty="true|false"` los elementos
 * de bloque que hay dentro de cada zona editable (para sus marcadores de
 * posición), y las herramientas que guardan `innerHTML` se las llevan al JSON:
 * la alerta de la página 8 salía con `<div data-empty="false">`, que acababa
 * en el HTML que se sirve.
 */
function stripEditorMarks(value) {
    if (typeof value === 'string') {
        return value.replace(/\sdata-empty="(?:true|false)"/g, '');
    }

    if (Array.isArray(value)) {
        return value.map(stripEditorMarks);
    }

    if (value && typeof value === 'object') {
        return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, stripEditorMarks(item)]));
    }

    return value;
}

function editorJsField({ state, placeholder, readOnly, allowRaw = false, endpoints = {} }) {
    return {
        editor: null,
        observer: null,
        state,

        // Última serialización volcada al state, para distinguir cambios
        // propios (onChange) de cambios externos (carga del registro).
        lastSaved: null,

        init() {
            this.lastSaved = this.normalize(this.state);
            this.initEditor();

            // Cambios externos al state (p. ej. al abrir el modal de edición).
            this.$watch('state', (value) => {
                const normalized = this.normalize(value);

                if (! this.editor || normalized === this.lastSaved) {
                    return;
                }

                this.lastSaved = normalized;

                this.editor.isReady.then(() => {
                    const parsed = this.parse(normalized);

                    if (parsed.blocks.length > 0) {
                        this.editor.render(parsed);
                    } else {
                        this.editor.clear();
                    }
                });
            });
        },

        normalize(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            return typeof value === 'string' ? value : JSON.stringify(value);
        },

        parse(normalized) {
            if (! normalized) {
                return { blocks: [] };
            }

            try {
                const parsed = JSON.parse(normalized);

                return parsed && Array.isArray(parsed.blocks) ? parsed : { blocks: [] };
            } catch (e) {
                return { blocks: [] };
            }
        },

        // Contenido del editor tal como se guarda.
        async serialize() {
            await this.editor.isReady;

            return JSON.stringify(stripEditorMarks(await this.editor.save()));
        },

        // Vuelca el contenido actual del editor al state entangled de Livewire.
        async flush() {
            if (! this.editor) {
                return;
            }

            try {
                const json = await this.serialize();

                this.lastSaved = json;
                this.state = json;
            } catch (e) {
                console.error('EditorJS: error al volcar el contenido', e);
            }
        },

        initEditor() {
            const holder = this.$refs.editor;

            this.editor = new EditorJS({
                holder,
                autofocus: false,
                readOnly,
                placeholder,
                data: this.parse(this.lastSaved),
                tools: buildTools(endpoints ?? {}, allowRaw, (message) => this.editor?.notifier.show({ message, style: 'error', time: 8000 })),
                tunes: ['textVariant'],
                i18n: { messages },
                onChange: () => this.flush(),
            });

            translateHardcoded(holder);
            this.observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    const target = mutation.type === 'characterData' ? mutation.target.parentNode : mutation.target;

                    if (target) {
                        translateHardcoded(target);
                    }
                });
            });
            this.observer.observe(holder, {
                subtree: true,
                childList: true,
                characterData: true,
                attributes: true,
                attributeFilter: ['data-placeholder'],
            });

            // Red de seguridad: al salir el foco del editor (p. ej. mousedown
            // sobre el botón «Guardar») se vuelca el último cambio antes de
            // que Livewire serialice el formulario.
            holder.addEventListener('focusout', () => this.flush());
        },

        destroy() {
            this.observer?.disconnect();
            this.observer = null;

            if (this.editor && typeof this.editor.destroy === 'function') {
                this.editor.destroy();
            }

            this.editor = null;
        },
    };
}

// El módulo se ejecuta antes de que Livewire arranque Alpine, pero si llegara
// tarde (navegación con `wire:navigate`), Alpine ya existe y basta con
// registrarlo: el campo se monta después, al abrir el modal.
const register = () => window.Alpine.data('editorJsField', editorJsField);

if (window.Alpine) {
    register();
} else {
    document.addEventListener('alpine:init', register);
}
