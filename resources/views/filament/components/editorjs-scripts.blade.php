{{--
    Scripts de Editor.js y registro del componente Alpine "editorJsField".

    Se inyectan mediante render hook del panel (ver AdminPanelProvider) porque
    los formularios de los modales se montan por Livewire tras la carga de la
    página: un @push desde la vista del campo llegaría tarde y se descartaría.
--}}

{{-- Editor.js Core --}}
<script src="{{ asset('vendor/editorjs/editor.js') }}"></script>

{{-- Plugins --}}
<script src="{{ asset('vendor/editorjs/header.js') }}"></script>
<script src="{{ asset('vendor/editorjs/delimiter.js') }}"></script>
<script src="{{ asset('vendor/editorjs/editorjs-alert.js') }}"></script>
<script src="{{ asset('vendor/editorjs/list.js') }}"></script>
<script src="{{ asset('vendor/editorjs/checklist.js') }}"></script>
<script src="{{ asset('vendor/editorjs/quote.js') }}"></script>
<script src="{{ asset('vendor/editorjs/embed.js') }}"></script>
<script src="{{ asset('vendor/editorjs/table.js') }}"></script>
<script src="{{ asset('vendor/editorjs/raw.js') }}"></script>
<script src="{{ asset('vendor/editorjs/simple-image.js') }}"></script>
<script src="{{ asset('vendor/editorjs/code.js') }}"></script>
<script src="{{ asset('vendor/editorjs/warning.js') }}"></script>
<script src="{{ asset('vendor/editorjs/marker.js') }}"></script>
<script src="{{ asset('vendor/editorjs/inline-code.js') }}"></script>
<script src="{{ asset('vendor/editorjs/text-variant-tune.js') }}"></script>
<script src="{{ asset('vendor/editorjs/codeflask.js') }}"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('editorJsField', ({ state, placeholder, readOnly }) => ({
            editor: null,
            state,

            // Última serialización volcada al state, para distinguir cambios
            // propios (onChange) de cambios externos (carga del registro).
            lastSaved: null,

            init() {
                if (typeof EditorJS === 'undefined') {
                    this.$refs.editor.innerHTML =
                        '<p style="color: rgb(220 38 38); font-size: 0.875rem;">'
                        + 'No se pudo cargar Editor.js. Comprueba que existen los assets en public/vendor/editorjs.'
                        + '</p>';

                    return;
                }

                this.lastSaved = this.normalize(this.state);
                this.initEditor();

                // Cambios externos al state (p.ej. al abrir el modal de edición).
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

            // Vuelca el contenido actual del editor al state entangled de Livewire.
            async flush() {
                if (! this.editor) {
                    return;
                }

                try {
                    await this.editor.isReady;

                    const data = await this.editor.save();
                    const json = JSON.stringify(data);

                    this.lastSaved = json;
                    this.state = json;
                } catch (e) {
                    console.error('EditorJS: error al volcar el contenido', e);
                }
            },

            buildTools() {
                const optional = (name) => (typeof window[name] !== 'undefined' ? window[name] : null);

                const tools = {
                    header: { class: Header, config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } },
                    delimiter: Delimiter,
                    list: { class: optional('NestedList') ?? List, inlineToolbar: true },
                    checklist: { class: Checklist, inlineToolbar: true },
                    quote: { class: Quote, inlineToolbar: true },
                    table: { class: Table, inlineToolbar: true },
                };

                if (optional('editorjsAlert')) tools.alert = optional('editorjsAlert');
                if (optional('RawTool')) tools.raw = optional('RawTool');
                if (optional('SimpleImage')) tools.image = optional('SimpleImage');
                if (optional('Embed')) tools.embed = optional('Embed');
                if (optional('Warning')) tools.warning = optional('Warning');
                if (optional('Marker')) tools.Marker = { class: optional('Marker') };
                if (optional('InlineCode')) tools.inlineCode = { class: optional('InlineCode') };
                if (optional('TextVariantTune')) tools.textVariant = optional('TextVariantTune');

                if (optional('editorjsCodeflask')) {
                    tools.code = optional('editorjsCodeflask');
                } else if (optional('CodeTool')) {
                    tools.code = optional('CodeTool');
                }

                return tools;
            },

            initEditor() {
                this.editor = new EditorJS({
                    holder: this.$refs.editor,
                    autofocus: false,
                    readOnly: readOnly,
                    placeholder: placeholder,
                    data: this.parse(this.lastSaved),
                    tools: this.buildTools(),
                    tunes: typeof TextVariantTune !== 'undefined' ? ['textVariant'] : [],
                    i18n: {
                        messages: {
                            toolNames: {
                                'Text': 'Texto', 'Heading': 'Encabezado',
                                'List': 'Lista', 'Checklist': 'Checklist',
                                'Quote': 'Cita', 'Delimiter': 'Delimitador',
                                'Table': 'Tabla', 'Warning': 'Advertencia',
                                'Code': 'Código', 'Raw HTML': 'HTML Raw',
                                'Image': 'Imagen', 'Link': 'Enlace',
                            },
                            ui: {
                                'blockTunes': { 'toggler': { 'Click to tune': 'Configurar bloque' } },
                                'toolbar': { 'toolbox': { 'Add': 'Añadir' } },
                            },
                        },
                    },
                    onChange: () => this.flush(),
                });

                // Red de seguridad: al salir el foco del editor (p.ej. mousedown
                // sobre el botón "Guardar") se vuelca el último cambio antes de
                // que Livewire serialice el formulario.
                this.$refs.editor.addEventListener('focusout', () => this.flush());
            },

            destroy() {
                if (this.editor && typeof this.editor.destroy === 'function') {
                    this.editor.destroy();
                }

                this.editor = null;
            },
        }));
    });
</script>
