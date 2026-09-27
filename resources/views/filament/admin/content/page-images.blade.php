{{-- Imágenes de una página (H2), dentro del modal «Imágenes». Clases cpe-images* en panel.css. --}}
<div class="cpe-images">
    @forelse ($images as $card)
        @php
            $file = $card['file'];
            $usages = array_map(fn (array $usage): string => $usage['label'], $card['usages']);
            $version = $file->updated_at?->timestamp;
        @endphp
        <div
            class="cpe-images__card"
            wire:key="image-{{ $file->id }}-{{ $version }}"
            x-data="contentImageCropper(@js(['fileId' => $file->id, 'width' => (int) $file->width, 'height' => (int) $file->height, 'usages' => $usages]))"
        >
            <div class="cpe-images__preview" x-show="! cropping">
                <img src="{{ $file->thumbnail('small') }}?v={{ $version }}" alt="{{ $file->alt }}" loading="lazy">
            </div>

            <div class="cpe-images__cropper" x-show="cropping" x-cloak>
                <img x-ref="source" src="{{ $file->url }}?v={{ $version }}" alt="">
            </div>

            <div class="cpe-images__info">
                <p class="cpe-images__where">
                    @if ($card['blocks'] !== [])
                        {{ count($card['blocks']) === 1 ? 'Bloque' : 'Bloques' }} {{ implode(', ', $card['blocks']) }}
                    @endif
                    @if ($card['cover'])
                        {{ $card['blocks'] !== [] ? '· ' : '' }}Portada de la página
                    @endif
                </p>
                <p class="cpe-images__meta">{{ $file->width }} × {{ $file->height }} px · {{ \Illuminate\Support\Number::fileSize((int) $file->size, precision: 1) }}</p>

                @if ($usages !== [])
                    <p class="cpe-images__usages">También en: {{ implode(' · ', $usages) }}</p>
                @endif

                <div class="cpe-images__texts" x-data="{ title: @js((string) $file->title), alt: @js((string) $file->alt) }">
                    <label>Título <input type="text" x-model="title" maxlength="511" class="cpe-images__input"></label>
                    <label>Texto alternativo <input type="text" x-model="alt" maxlength="511" class="cpe-images__input"></label>
                    <x-filament::button size="sm" color="gray" x-on:click="$wire.saveImageTexts({{ $file->id }}, title, alt)">Guardar textos</x-filament::button>
                </div>

                <div class="cpe-images__buttons">
                    <x-filament::button size="sm" color="gray" icon="heroicon-m-scissors" x-show="! cropping" x-on:click="start()">Recortar</x-filament::button>
                    <x-filament::button size="sm" icon="heroicon-m-check" x-show="cropping" x-cloak x-on:click="apply()">Aplicar recorte</x-filament::button>
                    <x-filament::button size="sm" color="gray" x-show="cropping" x-cloak x-on:click="cancel()">Cancelar</x-filament::button>
                    <label class="cpe-images__replace" x-show="! cropping">
                        <x-filament::icon icon="heroicon-m-arrow-up-tray" class="cpe-list__icon" /> Sustituir
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/heic,image/avif"
                            x-on:change="$event.target.files[0] && $wire.upload('replacement', $event.target.files[0], () => $wire.replaceImage({{ $file->id }}))"
                        >
                    </label>
                </div>
            </div>
        </div>
    @empty
        <p class="cpe-history__empty">Esta página no tiene imágenes.</p>
    @endforelse
</div>
