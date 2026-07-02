<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Galleries\RelationManagers;

use App\Filament\Components\ImageCropperUpload;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImagesRelationManager extends RelationManager
{
    use HasImageFileUpload;

    protected static string $relationship = 'images';

    protected static ?string $title = 'Imágenes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            ImageCropperUpload::makeImage('image_id')
                ->storeFiles(false)
                ->required()
                ->columnSpanFull()
                ->label('Imagen'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('image.fileType'))
            ->columns([
                ImageColumn::make('image_id')
                    ->getStateUsing(fn ($record) => $record->image?->thumbnail('small'))
                    ->square()
                    ->label('Vista previa'),
                TextColumn::make('created_at')->dateTime('d/m/Y')->label('Subida'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Añadir Imagen')
                    ->mutateFormDataUsing(function (array $data) {
                        $data = $this->resolveImageUpload($data, 'image_id', 'galleries');

                        // Si la subida todavía no había terminado al enviar el
                        // formulario (p. ej. al pulsar «Crear y crear otra» antes
                        // de tiempo), image_id llega vacío: se aborta en vez de
                        // crear una GalleryImage sin imagen.
                        if (blank($data['image_id'] ?? null)) {
                            Notification::make()
                                ->danger()
                                ->title('La imagen todavía se está subiendo')
                                ->body('Espera a que termine de subirse antes de guardar.')
                                ->send();

                            throw new Halt;
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make()->action(fn ($record) => $record->safeDelete()),
            ]);
    }
}
