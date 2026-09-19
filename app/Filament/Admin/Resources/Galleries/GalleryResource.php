<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Galleries;

use App\Enums\GalleryAspectRatioEnum;
use App\Filament\Admin\Resources\Galleries\Pages\CreateGallery;
use App\Filament\Admin\Resources\Galleries\Pages\EditGallery;
use App\Filament\Admin\Resources\Galleries\Pages\ListGalleries;
use App\Filament\Components\ImageCropperUpload;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\Gallery;
use App\Models\Hardware\HardwareComponent;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class GalleryResource extends Resource
{
    use ScopesToOwner;

    protected static ?string $model = Gallery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 35;

    protected static ?string $modelLabel = 'Galería';

    protected static ?string $pluralModelLabel = 'Galerías';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('GalleryTabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Detalles')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(511)
                                    ->autofocus()
                                    ->live(onBlur: true)
                                    ->placeholder('Ej: Montaje estación meteorológica Chipiona')
                                    ->label('Nombre de la galería')
                                    ->helperText('Obligatorio. Nombre descriptivo de la galería (máx. 511 caracteres).')
                                    ->validationMessages([
                                        'required' => 'Debes indicar un nombre para la galería antes de guardar.',
                                    ]),

                                Select::make('aspect_ratio')
                                    ->options(GalleryAspectRatioEnum::options())
                                    ->default(GalleryAspectRatioEnum::Wide16x9->value)
                                    ->required()
                                    ->live()
                                    ->label('Relación de aspecto')
                                    ->helperText(fn (string $operation): string => $operation === 'edit'
                                        ? 'Aviso: Cambiar la relación de aspecto afectará al recorte de las nuevas fotos que subas. Las fotos ya existentes mantendrán su recorte actual.'
                                        : 'Define la proporción fija que se aplicará en el editor de recorte para todas las fotos de esta galería (16:9 panorámico recomendado).'
                                    ),
                            ]),

                            Select::make('user_id')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->default(fn () => auth()->id())
                                ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                                ->label('Usuario propietario')
                                ->helperText('Usuario al que pertenecerá la galería. Por defecto, el usuario conectado.'),

                            Textarea::make('description')
                                ->maxLength(1024)
                                ->rows(2)
                                ->columnSpanFull()
                                ->placeholder('Descripción opcional sobre las fotografías de esta galería...')
                                ->label('Descripción')
                                ->helperText('Opcional. Breve contexto o notas sobre esta colección de fotos (máx. 1024 caracteres).'),

                            Placeholder::make('cover_preview')
                                ->label('Portada actual de la galería')
                                ->visible(fn (string $operation): bool => $operation === 'edit')
                                ->content(function (?Gallery $record): ?HtmlString {
                                    if (! $record) {
                                        return null;
                                    }

                                    $firstImage = $record->images()->orderBy('order', 'asc')->first();
                                    $coverFile = $firstImage ? $firstImage->image : $record->image;

                                    if (! $coverFile) {
                                        return new HtmlString('<p class="text-sm text-gray-400">Sin fotos todavía. Sube fotos en la sección inferior para fijar la portada automáticamente.</p>');
                                    }

                                    $url = $coverFile->thumbnail('medium') ?? $coverFile->url;
                                    $caption = $firstImage?->caption ? ' · '.e($firstImage->caption) : '';

                                    return new HtmlString(
                                        '<div class="flex items-center gap-4 p-3 rounded-lg bg-gray-900/60 border border-gray-800">
                                            <img src="'.e($url).'" class="h-20 rounded-md object-cover shadow" style="aspect-ratio: 16/9;" alt="Portada actual" />
                                            <div class="text-xs text-gray-300 space-y-1">
                                                <p class="font-semibold text-amber-400">
                                                    ★ La primera foto de la lista inferior (Posición #1) es siempre la portada'.$caption.'
                                                </p>
                                                <p class="text-gray-400">
                                                    Para cambiar la portada, simplemente reordena las fotos arrastrándolas o pulsa «Poner de portada» en la foto deseada en el gestor inferior.
                                                </p>
                                            </div>
                                        </div>'
                                    );
                                }),

                            ImageCropperUpload::makeImage('image_id', 'portada')
                                ->asFileRecord()
                                ->storeFiles(false)
                                ->maxSize(15360)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->label('Imagen de portada')
                                ->helperText('Opcional: Si la dejas vacía, la primera foto que subas abajo se convertirá automáticamente en la portada.')
                                ->visible(fn (string $operation): bool => $operation === 'create')
                                ->imageEditorViewportWidth(function ($get): ?int {
                                    $ratio = $get('aspect_ratio');
                                    $enum = is_string($ratio) ? GalleryAspectRatioEnum::tryFrom($ratio) : $ratio;

                                    return $enum instanceof GalleryAspectRatioEnum ? $enum->viewportWidth() : 16;
                                })
                                ->imageEditorViewportHeight(function ($get): ?int {
                                    $ratio = $get('aspect_ratio');
                                    $enum = is_string($ratio) ? GalleryAspectRatioEnum::tryFrom($ratio) : $ratio;

                                    return $enum instanceof GalleryAspectRatioEnum ? $enum->viewportHeight() : 9;
                                })
                                ->imageEditorAspectRatios(function ($get): array {
                                    $ratio = $get('aspect_ratio');
                                    $enum = is_string($ratio) ? GalleryAspectRatioEnum::tryFrom($ratio) : $ratio;

                                    return $enum ? $enum->cropperAspectRatios() : ['16:9'];
                                }),

                            Section::make('Subida inicial de fotos')
                                ->description('Arrastra o selecciona las fotos para crear la galería completa de una vez. Se recortarán con la proporción fijada arriba.')
                                ->visible(fn (string $operation): bool => $operation === 'create')
                                ->schema([
                                    FileUpload::make('uploaded_images')
                                        ->multiple()
                                        ->image()
                                        ->imageEditor()
                                        ->imageEditorViewportWidth(function ($get): ?int {
                                            $ratio = $get('aspect_ratio');
                                            $enum = is_string($ratio) ? GalleryAspectRatioEnum::tryFrom($ratio) : $ratio;

                                            return $enum instanceof GalleryAspectRatioEnum ? $enum->viewportWidth() : 16;
                                        })
                                        ->imageEditorViewportHeight(function ($get): ?int {
                                            $ratio = $get('aspect_ratio');
                                            $enum = is_string($ratio) ? GalleryAspectRatioEnum::tryFrom($ratio) : $ratio;

                                            return $enum instanceof GalleryAspectRatioEnum ? $enum->viewportHeight() : 9;
                                        })
                                        ->imageEditorAspectRatios(function ($get): array {
                                            $ratio = $get('aspect_ratio');
                                            $enum = is_string($ratio) ? GalleryAspectRatioEnum::tryFrom($ratio) : $ratio;

                                            return $enum ? $enum->cropperAspectRatios() : ['16:9'];
                                        })
                                        ->maxFiles(20)
                                        ->maxSize(15360)
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                        ->storeFiles(false)
                                        ->reorderable()
                                        ->label('Fotos de la galería')
                                        ->helperText('Formatos admitidos: JPG, PNG, WEBP, GIF. Máximo 15 MB por foto y hasta 20 fotos por tanda. La primera foto subida se convertirá automáticamente en la portada.')
                                        ->validationMessages([
                                            'uploaded' => 'No se ha podido subir alguna de las imágenes. Comprueba que pesen menos de 15 MB y tengan un formato admitido.',
                                            'max_files' => 'Puedes subir un máximo de 20 fotos por tanda. Una vez creada la galería, podrás añadir más fotos desde su ficha.',
                                        ])
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Asociaciones')
                        ->icon('heroicon-o-link')
                        ->visible(fn (string $operation): bool => $operation === 'edit')
                        ->schema([
                            Select::make('contents')
                                ->relationship(
                                    name: 'contents',
                                    titleAttribute: 'title',
                                    modifyQueryUsing: fn (Builder $query) => $query->select(['contents.id', 'contents.title'])->reorder('contents.title', 'asc'),
                                )
                                ->multiple()
                                ->searchable()
                                ->visible(fn (string $operation): bool => $operation === 'edit')
                                ->label('Contenidos del CMS')
                                ->helperText('Artículos o publicaciones que incluyen esta galería.'),

                            Select::make('hardwareDevices')
                                ->relationship(
                                    name: 'hardwareDevices',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query) => $query->select(['hardware_devices.id', 'hardware_devices.name'])->reorder('hardware_devices.name', 'asc'),
                                )
                                ->multiple()
                                ->searchable()
                                ->visible(fn (string $operation): bool => $operation === 'edit')
                                ->label('Dispositivos Hardware')
                                ->helperText('Proyectos o periféricos de hardware vinculados a esta galería.'),

                            Select::make('hardwareComponents')
                                ->relationship(
                                    name: 'hardwareComponents',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query) => $query->select(['hardware_components.id', 'hardware_components.name', 'hardware_components.hardware_device_id'])->with('hardwareDevice')->reorder('hardware_components.name', 'asc'),
                                )
                                ->getOptionLabelFromRecordUsing(fn (HardwareComponent $record): string => "{$record->name} ({$record->hardwareDevice->name})")
                                ->multiple()
                                ->searchable()
                                ->visible(fn (string $operation): bool => $operation === 'edit')
                                ->label('Componentes de Hardware')
                                ->helperText('Componentes específicos instalados vinculados a esta galería.'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['image.fileType', 'user']))
            ->columns([
                ImageColumn::make('image_id')
                    ->getStateUsing(fn (Gallery $record): ?string => $record->image?->thumbnail('small'))
                    ->square()
                    ->label('Portada'),
                TextColumn::make('name')->searchable()->sortable()->label('Nombre'),
                TextColumn::make('aspect_ratio')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof GalleryAspectRatioEnum ? $state->value : ($state ?? '16:9'))
                    ->color('info')
                    ->label('Formato'),
                TextColumn::make('images_count')->counts('images')->label('Fotos'),
                TextColumn::make('contents_count')->counts('contents')->label('Contenidos'),
                TextColumn::make('hardware_devices_count')->counts('hardwareDevices')->label('Hardware'),
                TextColumn::make('user.name')->label('Usuario')->searchable(),
                TextColumn::make('created_at')->dateTime('d/m/Y')->label('Creada')->sortable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleries::route('/'),
            'create' => CreateGallery::route('/create'),
            'edit' => EditGallery::route('/{record}/edit'),
        ];
    }
}
