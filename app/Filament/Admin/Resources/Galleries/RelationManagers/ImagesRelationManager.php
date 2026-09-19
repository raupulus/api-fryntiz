<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Galleries\RelationManagers;

use App\Filament\Components\ImageCropperUpload;
use App\Filament\Concerns\HandlesBatchImageUploads;
use App\Models\File;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImagesRelationManager extends RelationManager
{
    use HandlesBatchImageUploads;

    protected static string $relationship = 'images';

    protected static bool $isLazy = false;

    protected static ?string $title = 'Fotos de la galería';

    public function form(Schema $schema): Schema
    {
        /** @var Gallery $gallery */
        $gallery = $this->getOwnerRecord();
        $cropRatio = $gallery->aspect_ratio?->cropRatio() ?? '16:9';

        return $schema->components([
            ImageCropperUpload::makeImage('image_id', 'Fotografía')
                ->asFileRecord()
                ->storeFiles(false)
                ->maxSize(15360)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->imageEditor()
                ->imageEditorViewportWidth($gallery->aspect_ratio?->viewportWidth() ?? 16)
                ->imageEditorViewportHeight($gallery->aspect_ratio?->viewportHeight() ?? 9)
                ->imageEditorAspectRatios(fn (): array => $gallery->aspect_ratio?->cropperAspectRatios() ?? ['16:9'])
                ->label('Fotografía')
                ->helperText('Haz clic en el lápiz sobre la imagen para recortarla de nuevo con la proporción ('.($gallery->aspect_ratio?->label() ?? '16:9').') o cambiarla.')
                ->required(),

            TextInput::make('caption')
                ->maxLength(511)
                ->label('Pie de foto')
                ->placeholder('Descripción opcional de la imagen')
                ->helperText('Texto explicativo visible que acompañará a la foto (máx. 511 caracteres).'),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var Gallery $gallery */
        $gallery = $this->getOwnerRecord();
        $cssRatio = $gallery->aspect_ratio?->cssAspectRatio() ?? '16/9';

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['image.fileType', 'gallery']))
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 3,
            ])
            ->paginated([24, 48, 'all'])
            ->defaultPaginationPageOption(24)
            ->reorderable('order')
            ->afterReordering(function () use ($gallery): void {
                $first = $gallery->images()->orderBy('order', 'asc')->first();
                if ($first) {
                    $gallery->update(['image_id' => $first->image_id]);
                }
            })
            ->defaultSort('order', 'asc')
            ->emptyStateHeading('No hay fotos en esta galería aún')
            ->emptyStateDescription('Añade imágenes pulsando el botón «Añadir fotos». La primera foto de la lista será la portada.')
            ->emptyStateIcon('heroicon-o-photo')
            ->emptyStateActions([
                $this->makeAddImagesAction($gallery, 'emptyAddImages'),
            ])
            ->columns([
                Stack::make([
                    ImageColumn::make('image_id')
                        ->getStateUsing(fn (GalleryImage $record): ?string => $record->image?->thumbnail('large') ?? $record->image?->thumbnail('medium') ?? $record->image?->url)
                        ->imageHeight('auto')
                        ->imageWidth('100%')
                        ->extraAttributes([
                            'class' => 'w-full',
                            'style' => 'width: 100% !important; align-self: stretch !important; display: block !important;',
                        ])
                        ->extraImgAttributes([
                            'class' => 'gl-card-image',
                            'style' => "width: 100% !important; height: auto !important; aspect-ratio: {$cssRatio} !important; min-height: 220px !important; object-fit: cover !important; border-radius: 0.5rem;",
                        ])
                        ->label('Foto'),

                    Split::make([
                        TextColumn::make('order_label')
                            ->badge()
                            ->state(function (GalleryImage $record): string {
                                return $record->order === 1
                                    ? '★ Portada'
                                    : 'Posición #'.$record->order;
                            })
                            ->color(fn (GalleryImage $record): string => $record->order === 1 ? 'warning' : 'gray')
                            ->grow(false),

                        TextColumn::make('caption')
                            ->limit(35)
                            ->placeholder('Sin pie de foto')
                            ->color('gray')
                            ->label('Pie de foto'),
                    ]),
                ])->space(2),
            ])
            ->recordAction('edit')
            ->headerActions([
                $this->makeAddImagesAction($gallery, 'addImages'),
            ])
            ->recordActions([
                Action::make('setAsCover')
                    ->label('Portada')
                    ->tooltip('Mover al primer puesto y fijar como portada')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->button()
                    ->size('xs')
                    ->hidden(fn (GalleryImage $record): bool => $record->order === 1)
                    ->requiresConfirmation()
                    ->modalHeading('Fijar como portada principal')
                    ->modalDescription('Esta fotografía pasará a la posición #1 y se establecerá como la portada principal de la galería.')
                    ->action(function (GalleryImage $record) use ($gallery): void {
                        $gallery->images()
                            ->where('id', '!=', $record->id)
                            ->where('order', '<=', $record->order)
                            ->increment('order');

                        $record->update(['order' => 1]);

                        // Re-normalizar órdenes secuenciales (1, 2, 3...)
                        $gallery->images()
                            ->orderBy('order', 'asc')
                            ->get()
                            ->each(function (GalleryImage $img, int $index): void {
                                $newOrder = $index + 1;
                                if ($img->order !== $newOrder) {
                                    $img->update(['order' => $newOrder]);
                                }
                            });

                        $gallery->update(['image_id' => $record->image_id]);

                        Notification::make()
                            ->success()
                            ->title('Portada actualizada')
                            ->body('Esta foto ahora ocupa la posición #1 y es la portada de la galería.')
                            ->send();
                    }),

                EditAction::make()
                    ->label('Editar')
                    ->tooltip('Editar pie de foto o recortar imagen')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->button()
                    ->size('xs')
                    ->modalHeading('Editar fotografía')
                    ->using(function (GalleryImage $record, array $data) use ($gallery): GalleryImage {
                        $rawImage = $data['image_id'] ?? null;

                        if ($rawImage && ! is_numeric($rawImage)) {
                            $uploadedFile = $this->resolveUploadedFile($rawImage);
                            if ($uploadedFile) {
                                $newFile = File::addFile($uploadedFile, 'galleries', is_private: false);
                                if ($newFile) {
                                    $oldFileId = $record->image_id;
                                    $data['image_id'] = $newFile->id;

                                    if ($record->order === 1) {
                                        $gallery->update(['image_id' => $newFile->id]);
                                    }

                                    if ($oldFileId && $oldFileId !== $newFile->id) {
                                        $oldFile = File::find($oldFileId);
                                        $oldFile?->safeDelete();
                                    }
                                }
                            }
                        }

                        $record->update([
                            'image_id' => is_numeric($data['image_id'] ?? null) ? (int) $data['image_id'] : $record->image_id,
                            'caption' => $data['caption'] ?? $record->caption,
                        ]);

                        return $record;
                    }),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->tooltip('Eliminar foto de la galería')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->button()
                    ->size('xs')
                    ->modalHeading('Eliminar foto de la galería')
                    ->modalDescription('¿Estás seguro de eliminar esta foto? El archivo se retirará de la galería y del almacenamiento.')
                    ->action(function (GalleryImage $record) use ($gallery): void {
                        $wasFirst = ($record->order === 1);
                        $record->safeDelete();

                        // Re-normalizar órdenes tras el borrado
                        $gallery->images()
                            ->orderBy('order', 'asc')
                            ->get()
                            ->each(function (GalleryImage $img, int $index): void {
                                $img->update(['order' => $index + 1]);
                            });

                        if ($wasFirst) {
                            $newFirst = $gallery->images()->orderBy('order', 'asc')->first();
                            $gallery->update(['image_id' => $newFirst?->image_id]);
                        }
                    }),
            ]);
    }

    protected function makeAddImagesAction(Gallery $gallery, string $name): Action
    {
        $cropRatio = $gallery->aspect_ratio?->cropRatio() ?? '16:9';

        return Action::make($name)
            ->label('Añadir fotos')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading('Añadir fotos a la galería')
            ->modalDescription(fn (): string => 'Selecciona o arrastra hasta 20 imágenes simultáneamente. El editor recortará automáticamente con la proporción de la galería ('.($gallery->aspect_ratio?->label() ?? '16:9').').')
            ->form([
                FileUpload::make('images')
                    ->multiple()
                    ->image()
                    ->imageEditor()
                    ->imageEditorViewportWidth($gallery->aspect_ratio?->viewportWidth() ?? 16)
                    ->imageEditorViewportHeight($gallery->aspect_ratio?->viewportHeight() ?? 9)
                    ->imageEditorAspectRatios(fn (): array => $gallery->aspect_ratio?->cropperAspectRatios() ?? ['16:9'])
                    ->maxFiles(20)
                    ->maxSize(15360)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->storeFiles(false)
                    ->reorderable()
                    ->label('Seleccionar fotos')
                    ->helperText('Formatos admitidos: JPG, PNG, WEBP, GIF. Máximo 15 MB por foto y hasta 20 fotos por tanda.')
                    ->validationMessages([
                        'uploaded' => 'No se ha podido subir alguna de las imágenes. Comprueba que pesen menos de 15 MB y tengan un formato válido.',
                        'max_files' => 'Puedes subir un máximo de 20 fotos por tanda.',
                    ])
                    ->required(),
            ])
            ->action(function (array $data): void {
                /** @var Gallery $gallery */
                $gallery = $this->getOwnerRecord();
                $images = $data['images'] ?? [];
                $maxOrder = (int) ($gallery->images()->max('order') ?? 0);

                $result = $this->attachBatchImagesToGallery($images, $gallery, startOrder: $maxOrder + 1);

                // Si la galería no tenía portada o era la primera subida, asegurar que la posición 1 sea la portada
                if (! $gallery->image_id || $maxOrder === 0) {
                    $first = $gallery->images()->orderBy('order', 'asc')->first();
                    if ($first) {
                        $gallery->update(['image_id' => $first->image_id]);
                    }
                }

                if ($result['failed'] > 0) {
                    Notification::make()
                        ->warning()
                        ->title('Atención al añadir fotos')
                        ->body("Se han añadido {$result['success']} fotos a la galería, pero {$result['failed']} archivo(s) no pudieron procesarse.")
                        ->persistent()
                        ->send();
                } else {
                    Notification::make()
                        ->success()
                        ->title("Se han añadido {$result['success']} fotos a la galería")
                        ->send();
                }
            });
    }
}
