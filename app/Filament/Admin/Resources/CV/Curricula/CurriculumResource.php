<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CV\Curricula;

use App\Enums\CurriculumVisibilityEnum;
use App\Filament\Admin\Resources\CV\Curricula\Pages\CreateCurriculum;
use App\Filament\Admin\Resources\CV\Curricula\Pages\EditCurriculum;
use App\Filament\Admin\Resources\CV\Curricula\Pages\ListCurricula;
use App\Filament\Components\ImageCropperUpload;
use App\Models\CV\Curriculum;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CurriculumResource extends Resource
{
    protected static ?string $model = Curriculum::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Curriculum';

    protected static ?string $pluralModelLabel = 'Curriculums';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()->searchable()->preload()
                    ->default(fn () => auth()->id())
                    ->label('Usuario'),
                ImageCropperUpload::makeImage('image_id')
                    ->cover16x9()
                    ->storeFiles(false)
                    ->dehydrated(fn ($state) => filled($state))
                    ->label('Imagen'),
                TextInput::make('title')
                    ->required()->label('Título')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, string $operation) {
                        // El slug se propone al escribir el título sólo al crear:
                        // cambiarlo en un CV ya publicado rompería su URL.
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label('Slug')
                    ->helperText('Es la URL del CV. Cambiarlo rompe los enlaces que ya hayas repartido.'),
                Textarea::make('presentation')
                    ->columnSpanFull()->label('Presentación'),

                // Visibilidad en tres estados, no un booleano (B1): el estado
                // intermedio es justo el que hace falta para mandarle a alguien
                // un CV hecho a medida sin publicarlo en internet.
                Select::make('visibility')
                    ->options(CurriculumVisibilityEnum::options())
                    ->default(CurriculumVisibilityEnum::Private->value)
                    ->required()
                    ->live()
                    ->label('Visibilidad')
                    ->helperText(fn (?string $state): string => $state === null
                        ? ''
                        : (CurriculumVisibilityEnum::tryFrom($state)?->description() ?? '')),

                Placeholder::make('enlace_compartido')
                    ->label('Enlace privado')
                    ->content(fn (?Curriculum $record): string => $record?->share_token
                        ? url('/cv/s/'.$record->share_token)
                        : 'Se genera al guardar con la visibilidad «Compartido por enlace».')
                    ->visible(fn ($get) => $get('visibility') === CurriculumVisibilityEnum::Shared->value)
                    ->columnSpanFull(),

                Toggle::make('is_active')->label('Activo')
                    ->default(true)
                    ->helperText('Desactivado no se sirve por ninguna vía, ni con el enlace privado.'),
                Toggle::make('is_downloadable')->label('Descargable en PDF'),
                Toggle::make('is_default')->label('Por defecto'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('image_id')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                IconColumn::make('is_downloadable')
                    ->label('Descargable')
                    ->boolean(),
                IconColumn::make('is_default')
                    ->label('Por defecto')
                    ->boolean(),
                IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\JobsRelationManager::class,
            RelationManagers\ProjectsRelationManager::class,
            RelationManagers\RepositoriesRelationManager::class,
            RelationManagers\ServicesRelationManager::class,
            RelationManagers\CollaborationsRelationManager::class,
            RelationManagers\HobbiesRelationManager::class,
            RelationManagers\SkillsRelationManager::class,
            RelationManagers\AcademicTrainingRelationManager::class,
            RelationManagers\AcademicComplementaryRelationManager::class,
            RelationManagers\AcademicComplementaryOnlineRelationManager::class,
            RelationManagers\ExperienceAccreditedRelationManager::class,
            RelationManagers\ExperienceNoAccreditedRelationManager::class,
            RelationManagers\ExperienceSelfEmployedRelationManager::class,
            RelationManagers\ExperienceAdditionalRelationManager::class,
            RelationManagers\ExperienceOtherRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurricula::route('/'),
            'create' => CreateCurriculum::route('/create'),
            'edit' => EditCurriculum::route('/{record}/edit'),
        ];
    }
}
