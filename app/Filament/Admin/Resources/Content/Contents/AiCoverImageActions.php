<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents;

use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\AiCoverImagesPage;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Enums\Width;
use Illuminate\Support\Js;
use Livewire\Component;

/**
 * Las acciones de portada con IA del campo `image_id`: «Generar con IA»,
 * «Mejorar con IA» y «Deshacer». Sólo salen en las páginas que cumplen
 * `AiCoverImagesPage` (con `HasAiCoverImages`); al crear una entrada, no (aún
 * no existe).
 */
class AiCoverImageActions
{
    /**
     * «Deshacer» lo hace el propio campo: pide al servidor cómo estaba, se lo
     * pone y vuelve a cargar sus ficheros. Si lo pusiera el servidor, el
     * componente de subida de Filament no repintaría: con el id de una
     * portada guardada (un número) su vigilante de estado falla.
     */
    public const UNDO_EVENT = 'ai-cover-undo';

    /**
     * Las acciones en el campo, y lo que necesita el modal para darle la
     * imagen elegida y «Deshacer» para devolverle la de antes.
     */
    public static function attach(FileUpload $field): FileUpload
    {
        return $field
            ->hintActions(self::make())
            // Para que el modal encuentre el campo y le dé la imagen elegida.
            ->extraAttributes(['data-ai-cover-field' => true])
            ->extraAlpineAttributes(fn (FileUpload $component): array => [
                'x-on:'.self::UNDO_EVENT.'.window' => 'if (pond) {'
                    .' const previous = await $wire.takeAiCoverUndo();'
                    .' shouldUpdateState = false;'
                    .' await $wire.set('.Js::from($component->getStatePath()).', previous);'
                    .' lastState = null;'
                    .' pond.files = await getFiles();'
                    .' shouldUpdateState = true;'
                    .' }',
            ]);
    }

    /**
     * @return list<Action>
     */
    public static function make(): array
    {
        return [
            Action::make('aiCoverUndo')
                ->label('Deshacer')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('gray')
                ->visible(fn (Component $livewire): bool => $livewire instanceof AiCoverImagesPage && $livewire->hasAiCoverUndo())
                ->alpineClickHandler('$dispatch('.Js::from(self::UNDO_EVENT).')'),

            self::modal('aiCoverImprove', 'improve')
                ->label('Mejorar con IA')
                ->icon('heroicon-m-paint-brush')
                ->modalHeading('Mejorar la portada con IA')
                ->modalDescription('Se manda la imagen del campo con lo que quieras cambiar. Eliges una de las que salgan y entra en el campo sin guardar.'),

            self::modal('aiCoverGenerate', 'generate')
                ->label('Generar con IA')
                ->icon('heroicon-m-sparkles')
                ->modalHeading('Generar la portada con IA')
                ->modalDescription('Describe la imagen y elige una de las que salgan: entra en el campo sin guardar.'),
        ];
    }

    private static function modal(string $name, string $mode): Action
    {
        return Action::make($name)
            ->color('gray')
            ->modalWidth(Width::FourExtraLarge)
            ->modalContent(fn (Component $livewire) => $livewire instanceof AiCoverImagesPage
                ? view('filament.admin.content.partials.ai-image-modal', $livewire->aiCoverModalData($mode))
                : null)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->visible(fn (Component $livewire): bool => $livewire instanceof AiCoverImagesPage && $livewire->aiCoverAvailable($mode));
    }
}
