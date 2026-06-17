<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="editorJsField({
            state: $wire.$entangle('{{ $getStatePath() }}'),
            placeholder: @js($getPlaceholder() ?? 'Escribe tu contenido aquí...'),
            readOnly: {{ $isDisabled() ? 'true' : 'false' }},
        })"
        x-init="init()"
    >
        <div
            x-ref="editor"
            class="min-h-[300px] border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 p-4"
        ></div>
    </div>
</x-dynamic-component>

@once
    @push('scripts')
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
                    state: state,

                    init() {
                        this.initEditor();

                        // Observar cambios externos al state (p.ej. al cargar el registro).
                        this.$watch('state', (value) => {
                            if (! this.editor || ! value) {
                                return;
                            }

                            try {
                                const parsed = typeof value === 'string' ? JSON.parse(value) : value;
                                if (parsed && parsed.blocks) {
                                    this.editor.isReady.then(() => this.editor.render(parsed));
                                }
                            } catch (e) {
                                // Ignorar JSON inválido externo.
                            }
                        });
                    },

                    parseState() {
                        if (! this.state) {
                            return { blocks: [] };
                        }

                        try {
                            const parsed = typeof this.state === 'string' ? JSON.parse(this.state) : this.state;
                            return parsed && parsed.blocks ? parsed : { blocks: [] };
                        } catch (e) {
                            return { blocks: [] };
                        }
                    },

                    initEditor() {
                        this.editor = new EditorJS({
                            holder: this.$refs.editor,
                            autofocus: false,
                            readOnly: readOnly,
                            placeholder: placeholder,
                            data: this.parseState(),
                            tools: {
                                header: { class: Header, config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } },
                                delimiter: Delimiter,
                                alert: typeof editorjsAlert !== 'undefined' ? editorjsAlert : undefined,
                                raw: typeof RawTool !== 'undefined' ? RawTool : undefined,
                                image: typeof SimpleImage !== 'undefined' ? SimpleImage : undefined,
                                list: { class: typeof NestedList !== 'undefined' ? NestedList : List, inlineToolbar: true },
                                checklist: { class: Checklist, inlineToolbar: true },
                                quote: { class: Quote, inlineToolbar: true },
                                embed: typeof Embed !== 'undefined' ? Embed : undefined,
                                table: { class: Table, inlineToolbar: true },
                                code: typeof editorjsCodeflask !== 'undefined' ? editorjsCodeflask : (typeof CodeTool !== 'undefined' ? CodeTool : undefined),
                                warning: typeof Warning !== 'undefined' ? Warning : undefined,
                                Marker: { class: typeof Marker !== 'undefined' ? Marker : undefined },
                                inlineCode: { class: typeof InlineCode !== 'undefined' ? InlineCode : undefined },
                                textVariant: typeof TextVariantTune !== 'undefined' ? TextVariantTune : undefined,
                            },
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
                            onChange: async () => {
                                try {
                                    const data = await this.editor.save();
                                    this.state = JSON.stringify(data);
                                } catch (e) {
                                    console.error('EditorJS save error:', e);
                                }
                            },
                        });
                    },

                    destroy() {
                        if (this.editor && typeof this.editor.destroy === 'function') {
                            this.editor.destroy();
                        }
                    },
                }));
            });
        </script>
    @endpush
@endonce
