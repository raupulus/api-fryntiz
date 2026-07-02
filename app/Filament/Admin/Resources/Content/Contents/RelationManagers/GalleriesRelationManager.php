<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\RelationManagers;

use App\Models\Gallery;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GalleriesRelationManager extends RelationManager
{
    protected static string $relationship = 'galleries';

    protected static ?string $title = 'Galerías';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            // Relación inversa explícita: Filament adivinaría "contents"
            // (plural del modelo padre Content), que sí existe en Gallery,
            // pero se deja explícito por claridad y consistencia con el
            // resto de RelationManagers de este recurso.
            ->inverseRelationship('contents')
            ->columns([
                TextColumn::make('name')->searchable()->label('Nombre'),
                TextColumn::make('images_count')->counts('images')->label('Imágenes'),
                TextColumn::make('created_at')->dateTime('d/m/Y')->label('Creada'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Añadir Galería')
                    // El label del botón ya se traduce arriba, pero el título
                    // del modal ("Vincular :label") usa el nombre del modelo
                    // relacionado, que sin esto queda en inglés ("Gallery").
                    ->modelLabel('Galería')
                    ->preloadRecordSelect()
                    // La miniatura de portada necesita image.fileType (usado
                    // por File::thumbnail()) precargado para no violar el
                    // lazy loading al construir las opciones del selector.
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->with('image.fileType'))
                    ->recordSelect(fn (Select $select) => $select->allowHtml())
                    ->recordTitle(function (Gallery $record): string {
                        // Estilos en línea: el panel Admin no compila un tema
                        // Tailwind custom, así que las clases utility no se
                        // generarían en ningún build (ver GalleryResource /
                        // docs/info/content.md).
                        $thumbnailStyle = 'width:1.5rem;height:1.5rem;border-radius:0.25rem;object-fit:cover;flex-shrink:0;';

                        $thumbnail = $record->image?->thumbnail('micro');

                        $thumbnailHtml = $thumbnail
                            ? '<img src="'.e($thumbnail).'" style="'.$thumbnailStyle.'" alt="" />'
                            : '<span style="'.$thumbnailStyle.'background:rgb(229 231 235);display:inline-block;"></span>';

                        return '<div style="display:flex;align-items:center;gap:0.5rem;">'.$thumbnailHtml.'<span>'.e($record->name).'</span></div>';
                    }),
            ])
            ->recordActions([
                Action::make('viewImages')
                    ->label('Ver imágenes')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->modalHeading(fn (Gallery $record): string => 'Imágenes de "'.$record->name.'"')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (Gallery $record) => view('filament.components.gallery-images-preview', [
                        'images' => $record->images()->with('image.fileType')->get(),
                    ])),
                DetachAction::make(),
            ]);
    }
}
