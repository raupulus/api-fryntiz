{{--
    Modal del asistente IA del editor de páginas de contenidos, con el
    proveedor que se elija (los activos con clave, el predeterminado primero).
    Estilos definidos en resources/css/filament/admin/panel.css (.cpe-ai-*).
--}}
<div
    class="cpe-ai"
    x-data="contentPageAiAssistant({
        streamUrl: @js($streamUrl),
        providers: @js($providers),
        reasoningLevels: @js(array_keys($reasoningLevels)),
        defaultReasoningLevel: @js($defaultReasoningLevel),
        pageId: @js($pageId),
        isNewPage: @js($isNewPage),
        sourceFormat: @js($currentFormat->value),
    })"
>
    {{-- Configuración: modo, proveedor y nivel de razonamiento --}}
    <div class="cpe-ai__controls">
        <div class="cpe-ai__field">
            <label class="cpe-ai__label" for="ai-mode">Modo de operación</label>
            <select id="ai-mode" class="cpe-ai__select" x-model="mode" :disabled="isStreaming">
                @foreach ($modes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if (count($providers) > 1)
            <div class="cpe-ai__field">
                <label class="cpe-ai__label" for="ai-provider">Proveedor</label>
                <select id="ai-provider" class="cpe-ai__select" x-model="provider" :disabled="isStreaming">
                    @foreach ($providers as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <div class="cpe-ai__field">
                <span class="cpe-ai__label">Proveedor</span>
                <span class="cpe-ai__fixed">{{ $providers[0]['label'] ?? '' }}</span>
            </div>
        @endif

        <div class="cpe-ai__field" x-show="hasReasoningLevels">
            <label class="cpe-ai__label" for="ai-thinking">Razonamiento</label>
            <select id="ai-thinking" class="cpe-ai__select" x-model="reasoningLevel" :disabled="isStreaming">
                @foreach ($reasoningLevels as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Instrucción / Prompt --}}
    <div class="cpe-ai__prompt-section">
        <label class="cpe-ai__label" for="ai-prompt">Instrucción</label>
        <textarea
            id="ai-prompt"
            class="cpe-ai__textarea"
            rows="3"
            x-model="prompt"
            :disabled="isStreaming"
            placeholder="Escribe lo que quieres que haga (ej. ampliar la explicación de la API, corregir ortografía, estructurar en pasos)..."
        ></textarea>

        {{-- Píldoras con sugerencias rápidas --}}
        <div class="cpe-ai__pills" x-show="!isStreaming">
            <span class="cpe-ai__pills-title">Sugerencias:</span>
            <button type="button" class="cpe-ai__pill" x-on:click="setSuggestion('Corrige la ortografía, tildes y optimiza la legibilidad del texto sin alterar el significado.')">
                Corregir ortografía y estilo
            </button>
            <button type="button" class="cpe-ai__pill" x-on:click="setSuggestion('Amplía las secciones con mayor detalle técnico y ejemplos claros.')">
                Ampliar técnicamente
            </button>
            <button type="button" class="cpe-ai__pill" x-on:click="setSuggestion('Resume y reorganiza el contenido en puntos clave concisos.')">
                Resumir en puntos clave
            </button>
            <button type="button" class="cpe-ai__pill" x-on:click="setSuggestion('Añade una introducción atractiva y un cierre con recomendaciones prácticas.')">
                Introducción y conclusiones
            </button>
        </div>
    </div>

    {{-- Botones de disparo / cancelación --}}
    <div class="cpe-ai__trigger-bar">
        <button
            type="button"
            class="cpe-ai__btn cpe-ai__btn--primary"
            x-show="!isStreaming"
            x-on:click="startStream()"
        >
            <x-filament::icon icon="heroicon-m-sparkles" class="cpe-ai__btn-icon" />
            <span x-text="(isFinished ? 'Regenerar con ' : 'Generar con ') + providerName"></span>
        </button>

        <button
            type="button"
            class="cpe-ai__btn cpe-ai__btn--danger"
            x-show="isStreaming"
            x-on:click="cancel()"
        >
            <x-filament::icon icon="heroicon-m-stop" class="cpe-ai__btn-icon" />
            <span>Detener generación</span>
        </button>

        {{-- Estado en vivo --}}
        <div class="cpe-ai__status" x-show="isStreaming">
            <span class="cpe-ai__spinner"></span>
            <span x-show="isThinking" x-text="providerName + ' está razonando...'"></span>
            <span x-show="!isThinking">Redactando contenido en tiempo real...</span>
        </div>
    </div>

    {{-- Mensaje de Error si ocurre --}}
    <div class="cpe-ai__error" x-show="errorMessage" x-cloak>
        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="cpe-ai__error-icon" />
        <span class="cpe-ai__error-text" x-text="errorMessage"></span>
    </div>

    {{-- Pensamiento (Thinking Reasoning) --}}
    <div class="cpe-ai__thought-container" x-show="thoughtText.length > 0" x-cloak>
        <details class="cpe-ai__thought" :open="isThinking">
            <summary class="cpe-ai__thought-summary">
                <span class="cpe-ai__thought-title">
                    <x-filament::icon icon="heroicon-m-light-bulb" class="cpe-ai__thought-icon" />
                    Razonamiento (resumen)
                </span>
                <span class="cpe-ai__thought-badge" x-show="isThinking">Pensando...</span>
            </summary>
            <pre class="cpe-ai__thought-body" x-text="thoughtText"></pre>
        </details>
    </div>

    {{-- Resultado del contenido en tiempo real / completado --}}
    <div class="cpe-ai__output-container" x-show="contentText.length > 0" x-cloak>
        <div class="cpe-ai__output-header">
            <span class="cpe-ai__output-title">Contenido generado</span>
            <template x-if="finalResult && finalResult.title">
                <span class="cpe-ai__output-meta">
                    Título sugerido: <strong x-text="finalResult.title"></strong>
                    (Slug: <code x-text="finalResult.slug"></code>)
                </span>
            </template>
        </div>

        <pre class="cpe-ai__output-content" x-text="displayText"></pre>

        {{-- Avisos de conversión si los hubo --}}
        <template x-if="finalResult && finalResult.warnings && finalResult.warnings.length > 0">
            <div class="cpe-ai__warnings">
                <strong>Avisos de adaptación de formato:</strong>
                <ul>
                    <template x-for="w in finalResult.warnings" :key="w">
                        <li x-text="w"></li>
                    </template>
                </ul>
            </div>
        </template>

        {{-- Botones de Aplicación al Editor --}}
        <div class="cpe-ai__apply-bar" x-show="isFinished && !errorMessage">
            <template x-if="mode === 'create'">
                <button
                    type="button"
                    class="cpe-ai__btn cpe-ai__btn--success"
                    x-on:click="apply(false)"
                >
                    <x-filament::icon icon="heroicon-m-plus-circle" class="cpe-ai__btn-icon" />
                    <span>Crear nueva página con este contenido</span>
                </button>
            </template>

            <template x-if="mode !== 'create'">
                <div class="cpe-ai__apply-group">
                    <button
                        type="button"
                        class="cpe-ai__btn cpe-ai__btn--success"
                        x-on:click="apply(false)"
                    >
                        <x-filament::icon icon="heroicon-m-arrow-path" class="cpe-ai__btn-icon" />
                        <span>Reemplazar contenido actual</span>
                    </button>
                    <button
                        type="button"
                        class="cpe-ai__btn cpe-ai__btn--secondary"
                        x-on:click="apply(true)"
                    >
                        <x-filament::icon icon="heroicon-m-bars-arrow-down" class="cpe-ai__btn-icon" />
                        <span>Añadir al final del actual</span>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
