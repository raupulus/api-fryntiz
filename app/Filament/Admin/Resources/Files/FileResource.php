<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Files;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Files\Pages\ListFiles;
use App\Models\File;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Auditoría y limpieza de archivos almacenados en `files` (públicos, privados
 * y huérfanos). La subida se realiza siempre desde cada módulo correspondiente.
 */
class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Archivo';

    protected static ?string $pluralModelLabel = 'Archivos';

    protected static ?string $navigationLabel = 'Archivos';

    protected static ?string $recordTitleAttribute = 'original_name';

    protected static ?string $slug = 'files';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['fileType', 'thumbnails', 'user']);
        $user = auth()->user();

        if ($user === null || ! $user->isAdmin()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q): void {
            $q->whereNull('user_id')
                ->orWhereNotIn(
                    'user_id',
                    User::query()->where('role_id', UserRoleEnum::SuperAdmin->value)->select('id')
                );
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('preview')
                    ->label('Vista')
                    ->getStateUsing(fn (File $record): ?string => $record->fileType?->type === 'image' ? $record->thumbnail('small') : null)
                    ->square()
                    ->size(40),
                TextColumn::make('original_name')
                    ->label('Archivo')
                    ->searchable(['original_name', 'name'])
                    ->limit(35)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('module')
                    ->label('Módulo')
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('fileType.mime')
                    ->label('Tipo MIME')
                    ->limit(30)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('size')
                    ->label('Tamaño')
                    ->formatStateUsing(static function (mixed $state): string {
                        $bytes = (int) $state;

                        if ($bytes >= 1_048_576) {
                            return number_format($bytes / 1_048_576, 2).' MB';
                        }

                        if ($bytes >= 1024) {
                            return number_format($bytes / 1024, 1).' KB';
                        }

                        return $bytes.' B';
                    })
                    ->sortable(),
                IconColumn::make('is_private')
                    ->label('Privado')
                    ->boolean(),
                TextColumn::make('user.name')
                    ->label('Propietario')
                    ->placeholder('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('path')
                    ->label('Ruta')
                    ->limit(35)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Subido el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_private')->label('Privado'),
                SelectFilter::make('module')
                    ->label('Módulo')
                    ->options(fn (): array => File::query()
                        ->whereNotNull('module')
                        ->distinct()
                        ->orderBy('module')
                        ->pluck('module', 'module')
                        ->all()),
                SelectFilter::make('file_type_id')
                    ->relationship('fileType', 'mime')
                    ->label('Tipo MIME'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Abrir')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (File $record): string => $record->url, shouldOpenInNewTab: true),
                DeleteAction::make()
                    ->modalHeading('¿Eliminar archivo del disco?')
                    ->modalDescription('Esta acción elimina permanentemente el archivo físico y todas sus miniaturas del almacenamiento.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFiles::route('/'),
        ];
    }
}
