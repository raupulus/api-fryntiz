{{--
    Portadas con IA: generar de 1 a 5 o mejorar la que hay, y poner la elegida
    en el campo de portada. Componente Alpine `contentAiImages`
    (resources/js/filament/ai-images.js). Reutiliza los estilos del asistente
    de texto (.cpe-ai-*) y añade los suyos (.cpe-aiimg-*) en panel.css.
--}}
<div
    class="cpe-ai cpe-aiimg"
    x-data="contentAiImages({
        streamUrl: @js($streamUrl),
        providers: @js($providers),
        mode: @js($mode),
        source: @js($sourceToken),
        pageId: @js($pageId),
    })"
>
    @if ($mode === 'improve' && filled($sourceUrl))
        <div class="cpe-aiimg__source">
            <span class="cpe-ai__label">Imagen que se mejora</span>
            <img src="{{ $sourceUrl }}" alt="Portada actual" class="cpe-aiimg__source-img">
        </div>
    @endif

    <div class="cpe-ai__controls">
        @if (count($providers) > 1)
            <div class="cpe-ai__field">
                <label class="cpe-ai__label" for="aiimg-provider">Proveedor</label>
                <select id="aiimg-provider" class="cpe-ai__select" x-model="provider" :disabled="isWorking">
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

        <div class="cpe-ai__field">
            <label class="cpe-ai__label" for="aiimg-count">Cuántas</label>
            <select id="aiimg-count" class="cpe-ai__select" x-model.number="count" :disabled="isWorking">
                @foreach (range(1, $maxImages) as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>

        <div class="cpe-ai__field" x-show="qualities.length > 0">
            <label class="cpe-ai__label" for="aiimg-quality">Calidad</label>
            <select id="aiimg-quality" class="cpe-ai__select" x-model="quality" :disabled="isWorking">
                <template x-for="option in qualities" :key="option.value">
                    <option :value="option.value" x-text="option.label" :selected="option.value === quality"></option>
                </template>
            </select>
        </div>
    </div>

    @if ($colors !== [])
        <label class="cpe-aiimg__colors">
            <input type="checkbox" x-model="useColors" :disabled="isWorking">
            <span>Usar los colores de la plataforma</span>
            <span class="cpe-aiimg__swatches" aria-hidden="true">
                @foreach ($colors as $color)
                    <span class="cpe-aiimg__swatch" style="background: {{ $color['hex'] }}" title="{{ $color['hex'] }}{{ $color['role'] ? ' · '.$color['role']->label() : '' }}"></span>
                @endforeach
            </span>
        </label>
    @endif

    <div class="cpe-ai__prompt-section">
        <label class="cpe-ai__label" for="aiimg-prompt">{{ $mode === 'improve' ? 'Qué mejorar' : 'Qué tiene que salir' }}</label>
        <textarea
            id="aiimg-prompt"
            class="cpe-ai__textarea"
            rows="3"
            x-model="prompt"
            :disabled="isWorking"
            placeholder="{{ $mode === 'improve'
                ? 'Ej.: más luz y nitidez, quita el ruido, que el fondo sea más oscuro… Vacío: mejora la calidad, la nitidez y la luz.'
                : 'Ej.: un portátil con una terminal abierta, estilo ilustración plana. Vacío: ilustra el tema del contenido.' }}"
        ></textarea>
        <p class="cpe-aiimg__hint">El título, el extracto{{ $pageId ? ', la página' : '' }} y las reglas de una portada ya van solos. Sale apaisada (16:9); si llega en otro formato, se recorta al centro.</p>
    </div>

    <div class="cpe-ai__trigger-bar">
        <button type="button" class="cpe-ai__btn cpe-ai__btn--primary" x-show="! isWorking" x-on:click="start()">
            <x-filament::icon icon="heroicon-m-sparkles" class="cpe-ai__btn-icon" />
            <span x-text="(images.length ? 'Otra vez con ' : '{{ $mode === 'improve' ? 'Mejorar con ' : 'Generar con ' }}') + providerName"></span>
        </button>

        <button type="button" class="cpe-ai__btn cpe-ai__btn--danger" x-show="isWorking" x-on:click="cancel()">
            <x-filament::icon icon="heroicon-m-stop" class="cpe-ai__btn-icon" />
            <span>Detener</span>
        </button>

        <div class="cpe-ai__status" x-show="isWorking">
            <span class="cpe-ai__spinner"></span>
            <span x-text="waitText"></span>
        </div>
    </div>

    <div class="cpe-ai__error" x-show="errorMessage" x-cloak>
        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="cpe-ai__error-icon" />
        <span class="cpe-ai__error-text" x-text="errorMessage"></span>
    </div>

    <div class="cpe-aiimg__grid" x-show="images.length > 0" x-cloak>
        <template x-for="image in images" :key="image.url">
            <figure class="cpe-aiimg__card">
                <img :src="image.url" :alt="'Propuesta de ' + providerName" class="cpe-aiimg__img" loading="lazy">
                <figcaption class="cpe-aiimg__caption">
                    <span x-text="image.width + ' × ' + image.height"></span>
                    <button
                        type="button"
                        class="cpe-ai__btn cpe-ai__btn--success"
                        x-on:click="choose(image)"
                        :disabled="choosing !== null"
                    >
                        <x-filament::icon icon="heroicon-m-check" class="cpe-ai__btn-icon" />
                        <span x-text="choosing === image.url ? 'Poniéndola…' : 'Usar esta'"></span>
                    </button>
                </figcaption>
            </figure>
        </template>
    </div>
</div>
