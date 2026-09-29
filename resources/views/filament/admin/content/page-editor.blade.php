{{--
    Pantalla de páginas de un contenido (F8). Maquetación en panel.css,
    sección «Contenidos · pantalla de páginas» (clases cpe-*), sin Tailwind.
    El componente Alpine `contentPageEditor` está en
    resources/js/filament/content-pages.js.
--}}
@php
    $pages = $this->getPagesList();
    $marks = $this->getPagesMarks($pages);
    $trashed = $this->getTrashedPages();
    $format = \App\Enums\ContentPageFormatEnum::tryFrom((string) ($data['source_format'] ?? '')) ?? \App\Enums\ContentPageFormatEnum::EditorJs;
    $newUrl = $this->pageUrl(null);
@endphp

<x-filament-panels::page>
    <div
        class="cpe"
        x-data="contentPageEditor(@js([...$this->editorConfig(), 'lockToken' => $lockToken]))"
    >
        <aside class="cpe-list" aria-label="Páginas del contenido">
            {{-- En el móvil, un desplegable. --}}
            <select class="cpe-list__select" aria-label="Página" x-on:change="go($event.target.value)">
                @foreach ($pages as $item)
                    <option value="{{ $this->pageUrl($item) }}" @selected($item->id === $pageId)>{{ $loop->iteration }}. {{ $item->title }}{{ isset($marks['drafts'][$item->id]) ? ' · borrador' : '' }}{{ isset($marks['locked'][$item->id]) ? ' · en uso' : '' }}</option>
                @endforeach
                <option value="{{ $newUrl }}" @selected($pageId === null)>+ Añadir página{{ isset($marks['drafts']['new']) ? ' · borrador' : '' }}</option>
            </select>

            <ol
                class="cpe-list__items"
                x-sortable
                data-sortable-animation-duration="150"
                x-on:end.stop="$wire.reorderPages($event.target.sortable.toArray())"
            >
                @foreach ($pages as $item)
                    <li
                        wire:key="page-{{ $item->id }}"
                        x-sortable-item="{{ $item->id }}"
                        @class(['cpe-list__item', 'is-current' => $item->id === $pageId])
                    >
                        {{-- Con el teclado: foco en el asa y flechas arriba y abajo. --}}
                        <button
                            type="button"
                            x-sortable-handle
                            class="cpe-list__handle"
                            title="Arrastra para reordenar (o flechas arriba y abajo)"
                            aria-label="Mover «{{ $item->title }}» con las flechas arriba y abajo"
                            x-on:keydown.arrow-up.prevent="move({{ $item->id }}, -1)"
                            x-on:keydown.arrow-down.prevent="move({{ $item->id }}, 1)"
                        >
                            <x-filament::icon icon="heroicon-m-bars-2" class="cpe-list__icon" />
                        </button>
                        <a
                            href="{{ $this->pageUrl($item) }}"
                            data-page-link
                            x-on:click.prevent="go(@js($this->pageUrl($item)))"
                            class="cpe-list__link"
                        >
                            <span class="cpe-list__order">{{ $loop->iteration }}</span>
                            <span class="cpe-list__title">{{ $item->title }}</span>
                            @isset($marks['drafts'][$item->id])
                                <span class="cpe-list__flag" title="Tienes un borrador sin guardar de esta página">Borrador</span>
                            @endisset
                            @isset($marks['locked'][$item->id])
                                <span class="cpe-list__lock" title="{{ $marks['locked'][$item->id] }}" aria-label="{{ $marks['locked'][$item->id] }}">
                                    <x-filament::icon icon="heroicon-m-lock-closed" class="cpe-list__icon" />
                                </span>
                            @endisset
                        </a>
                    </li>
                @endforeach

                @if ($pageId === null)
                    <li class="cpe-list__item is-current">
                        <span class="cpe-list__link"><span class="cpe-list__order">{{ $pages->count() + 1 }}</span> <span class="cpe-list__title">Página nueva</span></span>
                    </li>
                @endif
            </ol>

            @if ($pageId !== null)
                <a href="{{ $newUrl }}" data-page-link x-on:click.prevent="go(@js($newUrl))" class="cpe-list__add">
                    <x-filament::icon icon="heroicon-m-plus" class="cpe-list__icon" /> Añadir página
                    @isset($marks['drafts']['new'])
                        <span class="cpe-list__flag" title="Tienes un borrador de una página nueva">Borrador</span>
                    @endisset
                </a>
            @endif

            @if ($trashed->isNotEmpty())
                <details class="cpe-trash">
                    <summary>Papelera ({{ $trashed->count() }})</summary>
                    <ul>
                        @foreach ($trashed as $item)
                            <li wire:key="trashed-{{ $item->id }}">
                                <span class="cpe-trash__title">{{ $item->title }}</span>
                                <x-filament::actions
                                    class="cpe-trash__actions"
                                    :actions="[($this->restorePageAction)(['page' => $item->id]), ($this->forceDeletePageAction)(['page' => $item->id])]"
                                />
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </aside>

        <section class="cpe-main">
            <div class="cpe-bar">
                <div class="cpe-bar__status">
                    <x-filament::badge color="gray">{{ $format->label() }}{{ filled($data['pending_change'] ?? null) ? ' (sin guardar)' : '' }}</x-filament::badge>
                    @if ($readOnly)
                        <x-filament::badge color="warning" icon="heroicon-m-lock-closed">En lectura</x-filament::badge>
                    @elseif ($pageId !== null)
                        <x-filament::badge color="success" icon="heroicon-m-pencil">Editando</x-filament::badge>
                    @endif
                    <span class="cpe-bar__text" x-text="autosaveText"></span>
                    <span class="cpe-bar__unsaved" x-show="unsaved" x-cloak>Cambios sin guardar</span>
                </div>

                <div class="cpe-bar__actions">
                    @unless ($readOnly)
                        <x-filament::button icon="heroicon-m-check" x-on:click="save()" title="Guardar (Ctrl/Cmd+S)">
                            Guardar
                        </x-filament::button>
                    @endunless
                    {{-- `<x-filament::actions>` y no `{{ $this->accion }}`: sólo
                         el componente mira si cada acción es visible. --}}
                    <x-filament::actions :actions="[$this->historyAction, $this->imagesAction, $this->deletePageAction]" />
                </div>
            </div>

            @if ($readOnly && filled($lockMessage))
                <div class="cpe-notice cpe-notice--lock" role="status">
                    <x-filament::icon icon="heroicon-m-lock-closed" class="cpe-notice__icon" />
                    <span class="cpe-notice__text">{{ $lockMessage }}</span>
                    <x-filament::actions :actions="[$this->takeOverAction, $this->forceUnlockAction]" />
                </div>
            @endif

            @if ($draftOffer)
                <div class="cpe-notice cpe-notice--draft" role="status">
                    <x-filament::icon icon="heroicon-m-document-text" class="cpe-notice__icon" />
                    <span class="cpe-notice__text">
                        Tienes un borrador de {{ $draftOffer['ago'] }}.
                        @if ($draftOffer['outdated'])
                            <strong>La página ha cambiado desde tu borrador.</strong>
                        @endif
                    </span>
                    <x-filament::actions :actions="[$this->restoreDraftAction, $this->discardDraftAction]" />
                </div>
            @endif

            <div class="cpe-form">
                {{ $this->form }}
            </div>
        </section>
    </div>
</x-filament-panels::page>
