<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers;

use App\Filament\Admin\Resources\Galleries\Pages\EditGallery;
use App\Models\Gallery;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GalleriesRelationManager extends RelationManager
{
    protected static string $relationship = 'galleries';

    protected static ?string $title = 'Galerías de fotos';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->inverseRelationship('hardwareDevices')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['image.fileType']))
            ->columns([
                ImageColumn::make('image_id')
                    ->getStateUsing(fn (Gallery $record): ?string => $record->image?->thumbnail('small'))
                    ->square()
                    ->label('Portada'),
                TextColumn::make('name')->searchable()->label('Nombre'),
                TextColumn::make('aspect_ratio')
                    ->badge()
                    ->color('info')
                    ->label('Formato'),
                TextColumn::make('images_count')->counts('images')->label('Fotos'),
                TextColumn::make('created_at')->dateTime('d/m/Y')->label('Creada'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Vincular Galería')
                    ->modelLabel('Galería')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->with('image.fileType')->withCount('images'))
                    ->recordSelect(fn (Select $select) => $select->allowHtml())
                    ->recordTitle(function (Gallery $record): string {
                        $thumbnailStyle = 'width:1.5rem;height:1.5rem;border-radius:0.25rem;object-fit:cover;flex-shrink:0;';
                        $thumbnail = $record->image?->thumbnail('micro');

                        $thumbnailHtml = $thumbnail
                            ? '<img src="'.e($thumbnail).'" style="'.$thumbnailStyle.'" alt="" />'
                            : '<span style="'.$thumbnailStyle.'background:rgb(229 231 235);display:inline-block;"></span>';

                        $count = $record->images_count ?? $record->images()->count();

                        return '<div style="display:flex;align-items:center;gap:0.5rem;">'.$thumbnailHtml.'<span>'.e($record->name).' ('.$count.' fotos)</span></div>';
                    }),
            ])
            ->recordActions([
                Action::make('viewImages')
                    ->label('Ver fotos')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->modalHeading(fn (Gallery $record): string => 'Fotos de "'.$record->name.'"')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (Gallery $record) => view('filament.components.gallery-images-preview', [
                        'images' => $record->images()->with('image.fileType')->get(),
                    ])),
                Action::make('editGallery')
                    ->label('Editar')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Gallery $record): string => EditGallery::getUrl(['record' => $record])),
                DetachAction::make(),
            ]);
    }
}
