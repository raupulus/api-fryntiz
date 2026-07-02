<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{--
        wire:ignore: Editor.js gestiona su propio DOM y Livewire no debe tocarlo.
        El componente Alpine "editorJsField" y los scripts de Editor.js se cargan
        a nivel de página mediante render hook (ver AdminPanelProvider y la vista
        filament/components/editorjs-scripts.blade.php). Alpine invoca init() y
        destroy() automáticamente, no añadir x-init.
    --}}
    <div
        wire:ignore
        x-data="editorJsField({
            state: $wire.$entangle('{{ $getStatePath() }}'),
            placeholder: @js($getPlaceholder() ?? 'Escribe tu contenido aquí...'),
            readOnly: @js($isDisabled()),
        })"
    >
        <div
            x-ref="editor"
            class="min-h-[300px] border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 p-4"
        ></div>
    </div>
</x-dynamic-component>
